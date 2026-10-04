<?php

namespace App\Modules\CMS\Services;

use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class ShopifyFileUploadService
{
    /** Shopify processes new files asynchronously; wait this long for the CDN URL. */
    private const READY_TIMEOUT_SECONDS = 25;

    public function upload(Store $store, UploadedFile $file, ?string $title = null): array
    {
        $client = new ShopifyClient($store);
        $stagedTarget = $this->createStagedUploadTarget($client, $file);
        $this->uploadBinaryToTarget($stagedTarget, $file);

        $createdFile = $this->createShopifyFile($client, $stagedTarget['resourceUrl'], $title, $file);

        if (!$createdFile) {
            throw new \RuntimeException('Shopify returned an empty file payload after upload.');
        }

        return $this->waitUntilReady($client, $createdFile);
    }

    /**
     * fileCreate returns before Shopify has processed the file, so the image URL is still
     * empty. Poll the file until it is READY (or FAILED) so the dashboard gets a real URL.
     */
    private function waitUntilReady(ShopifyClient $client, array $file): array
    {
        $id = (string) ($file['id'] ?? '');
        $deadline = microtime(true) + self::READY_TIMEOUT_SECONDS;

        while ($id !== '' && microtime(true) < $deadline) {
            $node = $client->query($this->fileStatusQuery(), ['id' => $id])['data']['node'] ?? null;

            if (!is_array($node)) {
                break;
            }

            $status = $node['fileStatus'] ?? null;

            if ($status === 'FAILED') {
                $message = collect($node['fileErrors'] ?? [])->pluck('message')->filter()->implode(' | ');
                throw new \RuntimeException($message ?: 'Shopify could not process this file.');
            }

            if ($status === 'READY') {
                return $node;
            }

            usleep(1_000_000);
        }

        return $file;
    }

    private function createStagedUploadTarget(ShopifyClient $client, UploadedFile $file): array
    {
        $variables = [
            'input' => [[
                'resource' => $this->stagedResource($file),
                'filename' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType() ?: 'application/octet-stream',
                'httpMethod' => 'POST',
                'fileSize' => (string) $file->getSize(),
            ]],
        ];

        $response = $client->query($this->stagedUploadMutation(), $variables);
        $payload = $response['data']['stagedUploadsCreate'] ?? null;

        $errors = $payload['userErrors'] ?? [];
        if (!empty($errors)) {
            $message = collect($errors)->pluck('message')->filter()->implode(' | ');
            throw new \RuntimeException($message ?: 'Shopify staged upload failed.');
        }

        $target = $payload['stagedTargets'][0] ?? null;
        if (!is_array($target) || empty($target['url']) || empty($target['resourceUrl'])) {
            throw new \RuntimeException('Shopify did not return a valid staged upload target.');
        }

        return $target;
    }

    private function uploadBinaryToTarget(array $target, UploadedFile $file): void
    {
        $formFields = [];
        foreach ($target['parameters'] ?? [] as $param) {
            $name = $param['name'] ?? null;
            $value = $param['value'] ?? null;

            if ($name === null || $value === null) {
                continue;
            }

            $formFields[(string) $name] = (string) $value;
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            throw new \RuntimeException('Unable to read uploaded file content.');
        }

        /** @var Response $response */
        $response = Http::asMultipart()
            ->attach('file', $content, $file->getClientOriginalName(), ['Content-Type' => $file->getMimeType() ?: 'application/octet-stream'])
            ->timeout(120)
            ->post($target['url'], $formFields);

        if (!$response->successful() && !in_array($response->status(), [201, 204], true)) {
            throw new \RuntimeException(sprintf(
                'Failed to upload binary to staged target. HTTP %s',
                $response->status()
            ));
        }
    }

    private function createShopifyFile(ShopifyClient $client, string $resourceUrl, ?string $title, UploadedFile $file): ?array
    {
        $variables = [
            'files' => [[
                'contentType' => $this->fileContentType($file),
                'originalSource' => $resourceUrl,
                'alt' => $title ? mb_substr(trim($title), 0, 255) : null,
            ]],
        ];

        $response = $client->query($this->filesCreateMutation(), $variables);
        $payload = $response['data']['fileCreate'] ?? null;

        $errors = $payload['userErrors'] ?? [];
        if (!empty($errors)) {
            $message = collect($errors)->pluck('message')->filter()->implode(' | ');
            throw new \RuntimeException($message ?: 'Shopify filesCreate failed.');
        }

        return $payload['files'][0] ?? null;
    }

    private function stagedResource(UploadedFile $file): string
    {
        $mime = strtolower((string) ($file->getMimeType() ?: ''));

        if (str_starts_with($mime, 'image/')) {
            return 'IMAGE';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'VIDEO';
        }

        return 'FILE';
    }

    private function fileContentType(UploadedFile $file): string
    {
        return match ($this->stagedResource($file)) {
            'IMAGE' => 'IMAGE',
            'VIDEO' => 'VIDEO',
            default => 'FILE',
        };
    }

    private function stagedUploadMutation(): string
    {
        return <<<'GRAPHQL'
mutation StagedUploadsCreate($input: [StagedUploadInput!]!) {
  stagedUploadsCreate(input: $input) {
    stagedTargets {
      url
      resourceUrl
      parameters {
        name
        value
      }
    }
    userErrors {
      field
      message
    }
  }
}
GRAPHQL;
    }

    private function fileStatusQuery(): string
    {
        return <<<'GRAPHQL'
query FileStatus($id: ID!) {
  node(id: $id) {
    __typename
    ... on File {
      id
      alt
      fileStatus
      fileErrors { code message }
    }
    ... on MediaImage {
      mimeType
      image { url width height }
    }
    ... on GenericFile {
      url
      mimeType
    }
    ... on Video {
      sources { url mimeType }
      preview { image { url width height } }
    }
  }
}
GRAPHQL;
    }

    private function filesCreateMutation(): string
    {
        return <<<'GRAPHQL'
mutation FilesCreate($files: [FileCreateInput!]!) {
  fileCreate(files: $files) {
    files {
      __typename
      ... on MediaImage {
        id
        alt
        image {
          url
          width
          height
        }
      }
      ... on GenericFile {
        id
        alt
        url
        mimeType
      }
      ... on Video {
        id
        alt
        sources {
          url
          mimeType
        }
        preview {
          image {
            url
            width
            height
          }
        }
      }
    }
    userErrors {
      field
      message
      code
    }
  }
}
GRAPHQL;
    }
}
