<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\PatientStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        $maleNames = ['محمد', 'أحمد', 'خالد', 'عمر', 'يامن', 'فراس', 'وائل', 'مصطفى', 'إبراهيم', 'علي'];
        $femaleNames = ['مريم', 'فاطمة', 'شام', 'ريم', 'لانا', 'تالا', 'نرمين', 'جوري', 'أمل', 'سارة'];
        $lastNames = ['الأحمد', 'الخطيب', 'السيد', 'الزين', 'العمر', 'المصري', 'الأسعد', 'النابلسي', 'الشيخ', 'البني'];
        $addresses = [
            'دمشق - المزة - شارع الجلاء', 'دمشق - كفرسوسة - حي المطار', 'دمشق - المالكي - شارع أبو رمانة',
            'دمشق - باب توما - الحريقة', 'دمشق - ركن الدين - شارع المدارس', 'دمشق - المهاجرين - شارع الروضة',
            'ريف دمشق - جرمانا', 'ريف دمشق - داريا', 'دمشق - برزة - شارع فيصل', 'دمشق - القابون - شارع الثورة'
        ];
        $diagnoses = [
            'ألم حاد في السن السفلي الأيمن مع تورم',
            'تسوس عميق في الضاحك العلوي',
            'نخور متعددة في الأسنان الأمامية',
            'التهاب لثة ونزف عند التفريش',
            'فقدان أسنان خلفية وحاجة للتعويض',
            'ألم عند شرب البارد والساخن',
            'انطمار في ضرس العقل',
            'تصبغات سنية وتراكم جير'
        ];

        $isMale = fake()->boolean();
        $gender = $isMale ? 'male' : 'female';
        $firstName = $isMale ? fake()->randomElement($maleNames) : fake()->randomElement($femaleNames);
        $lastName = fake()->randomElement($lastNames);

        return [
            'patient_code' => 'DU-' . date('Y') . '-' . fake()->unique()->numerify('####'),
            'full_name' => $firstName . ' ' . $lastName,
            'birth_date' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'gender' => $gender,
            'phone' => fake()->randomElement(['09', '011']) . fake()->numerify('#######'),
            'address' => fake()->randomElement($addresses),
            'preliminary_diagnosis' => fake()->randomElement($diagnoses),
            'availability_status' => PatientStatus::WAITING_DIAGNOSIS->value,
            'added_by' => User::factory(),
        ];
    }
}
