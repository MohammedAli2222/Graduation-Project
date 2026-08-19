<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use App\Support\CacheGroup;
use App\Support\CacheVersion;

class ProductObserver
{
    public function created(Product $product): void
    {
        $this->clearCache($product);
    }

    public function updated(Product $product): void
    {
        $this->clearCache($product);
    }

    public function deleted(Product $product): void
    {
        $this->clearCache($product);
    }

    // store_id عمود موحّد لكل من المتاجر الرسمية وبائعي الطلاب (لا يوجد جدول
    // stores منفصل)، فيكفي إبطال مجموعة المتجر نفسه بالإضافة لمجموعة سوق
    // الطلاب العامة (غير مقيّدة بمتجر واحد، فلا نعرف مسبقاً إن كان البائع طالباً)
    private function clearCache(Product $product): void
    {
        CacheVersion::bump(CacheGroup::store($product->store_id));
        CacheVersion::bump(CacheGroup::STUDENT_PRODUCTS);
    }
}
