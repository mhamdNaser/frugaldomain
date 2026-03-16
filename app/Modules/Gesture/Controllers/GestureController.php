<?php

namespace App\Modules\Gesture\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Gesture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class GestureController extends Controller
{
    /**
     * عرض الإيماءات مع Pagination و Cache
     */
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 50); // عدد الإيماءات لكل صفحة
            $page = (int) $request->get('page', 1);        // رقم الصفحة المطلوبة

            $cacheKey = "gestures_page_{$page}_per_{$perPage}";

            // محاولة قراءة الصفحة من الكاش
            $cachedPage = Cache::get($cacheKey);
            if ($cachedPage) {
                return Response::stream(function () use ($cachedPage) {
                    echo $cachedPage;
                }, 200, ['Content-Type' => 'application/json']);
            }

            // إذا الصفحة غير موجودة في الكاش، نجهزها من DB
            $gestures = Gesture::with(['frames.points'])
                ->orderBy('id')
                ->paginate($perPage, ['*'], 'page', $page);

            $formattedGestures = $gestures->getCollection()->map(function ($gesture) {
                $points = collect();
                foreach ($gesture->frames as $frame) {
                    foreach ($frame->points as $pt) {
                        $points->push([
                            'x' => $pt->x,
                            'y' => $pt->y,
                            'dx' => $pt->dx,
                            'dy' => $pt->dy,
                            'vx' => $pt->vx,
                            'vy' => $pt->vy,
                            'angle' => $pt->angle,
                            'pressure' => $pt->pressure,
                            'state' => $pt->state,
                            'timestamp' => $frame->timestamp,
                            'delta_ms' => $frame->delta_ms,
                            'frame_id' => $frame->frame_id,
                        ]);
                    }
                }
                $points = $points->sortBy('timestamp')->values();

                return [
                    'id' => $gesture->id,
                    'character' => $gesture->character,
                    'duration_ms' => $gesture->duration_ms,
                    'frame_count' => $gesture->frame_count,
                    'points_count' => $points->count(),
                    'points' => $points,
                ];
            });

            $responseJson = json_encode([
                'count' => $gestures->total(),
                'current_page' => $gestures->currentPage(),
                'last_page' => $gestures->lastPage(),
                'per_page' => $gestures->perPage(),
                'data' => $formattedGestures,
            ]);

            // تخزين الصفحة في الكاش لمدة 60 دقيقة
            Cache::put($cacheKey, $responseJson, now()->addMinutes(60));

            // إرسال الصفحة كـ JSON
            return Response::stream(function () use ($responseJson) {
                echo $responseJson;
            }, 200, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch gestures',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تخزين Gesture جديد وتحديث الكاش
     */
    public function store(Request $request)
    {
        $request->validate([
            'character' => 'required|string|max:255',
            'start_time' => 'required|integer',
            'end_time' => 'required|integer',
            'duration_ms' => 'required|integer',
            'frame_count' => 'required|integer',
            'frames' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $gesture = Gesture::create([
                'character' => $request->character,
                'user_id' => $request->user_id ?? null,
                'device_id' => $request->device_id ?? null,
                'start_time' => date('Y-m-d H:i:s', $request->start_time / 1000),
                'end_time' => date('Y-m-d H:i:s', $request->end_time / 1000),
                'duration_ms' => $request->duration_ms,
                'frame_count' => $request->frame_count,
                'notes' => $request->notes ?? null,
            ]);

            foreach ($request->frames as $frameData) {

                // فلترة النقاط التي إحداثياتها صفرية
                $filteredPoints = array_filter($frameData['points'], function ($pt) {
                    return !($pt['x'] == 0 && $pt['y'] == 0);
                });

                // إذا بقيت نقاط بعد الفلترة
                if (count($filteredPoints) === 0) {
                    continue; // تخطي هذا الفريم لأنه لا يحتوي على نقاط صالحة
                }

                $frame = $gesture->frames()->create([
                    'frame_id' => $frameData['frame_id'],
                    'timestamp' => $frameData['ts'],
                    'points_count' => count($filteredPoints),
                    'delta_ms' => $frameData['delta_ms'],
                ]);

                foreach ($filteredPoints as $pt) {
                    $frame->points()->create([
                        'point_id' => $pt['id'],
                        'x' => $pt['x'],
                        'y' => $pt['y'],
                        'dx' => $pt['dx'],
                        'dy' => $pt['dy'],
                        'vx' => $pt['vx'],
                        'vy' => $pt['vy'],
                        'angle' => $pt['angle'],
                        'state' => $pt['state'] ?? 'move',
                        'pressure' => $pt['pressure'] ?? 1.0,
                    ]);
                }
            }

            DB::commit();

            Cache::flush();

            return response()->json([
                'message' => 'Gesture saved successfully',
                'gesture_id' => $gesture->id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * عد الإيماءات حسب الحرف
     */
    public function countByCharacter($character)
    {
        try {
            $count = Gesture::where('character', $character)->count();
            return response()->json([
                'character' => $character,
                'count' => $count
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to count gestures',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
