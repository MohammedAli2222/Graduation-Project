<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessProductImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $productId معرف المنتج
     * @param array $tempPaths مسارات الصور المؤقتة
     */
    public function __construct(
        public int $productId,
        public array $tempPaths
    ) {}

   
    public function handle(): void
    {
        try {
            $product = Product::find($this->productId);

            if (! $product) {
                $this->cleanupTempFiles();
                return;
            }

            foreach ($this->tempPaths as $path) {

                if (Storage::disk('local')->exists($path)) {
                    $absolutePath = Storage::disk('local')->path($path);
                    

                    $product->addMedia($absolutePath)
                        ->toMediaCollection('products');
                }
            }

            $this->cleanupTempFiles();
            
        } catch (Exception $e) {
            Log::error("خطأ أثناء معالجة صور المنتج {$this->productId}: " . $e->getMessage());
            $this->cleanupTempFiles();
        }
    }

    
    protected function cleanupTempFiles(): void
    {
        foreach ($this->tempPaths as $path) {
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }
}