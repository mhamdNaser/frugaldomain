@echo off
setlocal

cd /d "%~dp0.."

set QUEUES=default,shopify-sync,shopify-variants,shopify-images,shopify-inventory,shopify-metafields,shopify-collections,shopify-files,shopify-customers,shopify-orders,shopify-draft-orders,shopify-fulfillments,shopify-financials,shopify-discounts,shopify-content

:loop
php artisan queue:work database --queue=%QUEUES% --sleep=1 --tries=3 --timeout=3600 --memory=256
timeout /t 5 /nobreak >nul
goto loop
