<?php

// تحديد المسارات المطلوبة بدقة لجمع معمارية المشروع
// جلب مجلد app بالكامل بالإضافة لمجلد التهجير ومسار الـ API
$paths = [
    'app',
    'database/migrations',
    'routes/api.php'
];

$outputFile = 'dentex_backend_context.txt';
$content = "Backend Architecture - Context Export\n";
$content .= "=================================================\n\n";

foreach ($paths as $path) {
    // التحقق مما إذا كان المسار يمثل ملفاً مباشراً (مثل مسارات الـ API)
    if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        $content .= "=================================================\n";
        $content .= "File: " . $path . "\n";
        $content .= "=================================================\n\n";
        $content .= file_get_contents($path) . "\n\n";
    }
    // التحقق مما إذا كان المسار يمثل مجلداً للمرور على جميع محتوياته الفرعية
    elseif (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            // تجاهل المجلدات الفارغة أو الملفات التي ليست PHP
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content .= "=================================================\n";
                $content .= "File: " . $file->getPathname() . "\n";
                $content .= "=================================================\n\n";
                $content .= file_get_contents($file->getPathname()) . "\n\n";
            }
        }
    }
}

// إنشاء الملف النصي النهائي وحفظ المحتوى بداخله
file_put_contents($outputFile, $content);
echo "تم تصدير ملفات المعمارية بنجاح إلى {$outputFile} \n";
