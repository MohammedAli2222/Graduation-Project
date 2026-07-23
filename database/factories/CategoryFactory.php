<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 *
 * The dental supplies taxonomy is a fixed, real-world domain rather than
 * infinite fake data, so this factory deliberately caps out at 20 unique,
 * curated categories (mirrors App\Enums via App\Models\Product columns).
 * Requesting more than 20 will exhaust Faker's unique() pool by design.
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    private const CATEGORIES = [
        'Endodontic Instruments (أدوات لبية)' =>
            'Root canal treatment tools, including K-files, reamers, and rotary NiTi systems.',
        'Impression Materials (مواد طبعات)' =>
            'Alginate, silicone, and PVS materials used to capture accurate dental impressions.',
        'Restorative Materials (مواد ترميمية)' =>
            'Composite resins, amalgam, glass ionomer cements, and bonding agents for fillings.',
        'Oral Surgery Instruments (أدوات جراحية)' =>
            'Extraction forceps, elevators, surgical curettes, and root tip instruments.',
        'Consumables & PPE (مستهلكات ومستلزمات الوقاية)' =>
            'Single-use clinical supplies such as gloves, masks, bibs, cotton rolls, and needles.',
        'Orthodontic Supplies (مستلزمات تقويم الأسنان)' =>
            'Brackets, archwires, elastic ligatures, and other appliances for teeth alignment.',
        'Prosthodontics & Crown Materials (مستلزمات التعويضات السنية)' =>
            'Materials and kits for fixed and removable crowns, bridges, and dentures.',
        'Periodontal Instruments (أدوات علاج اللثة)' =>
            'Scalers, curettes, and probes used for gum disease treatment and maintenance.',
        'Dental Anesthetics (مواد التخدير الموضعي)' =>
            'Local anesthetic cartridges, aspirating syringes, and topical anesthetic gels.',
        'Rotary Instruments & Handpieces (القطع اليدوية الدوارة)' =>
            'High- and low-speed handpieces, micromotors, and endodontic rotary engines.',
        'Diagnostic & Imaging Equipment (أجهزة التشخيص والأشعة)' =>
            'Apex locators, intraoral sensors, and portable dental X-ray units.',
        'Dental Chairs & Clinical Furniture (كراسي وأثاث العيادات السنية)' =>
            'Dental chairs, portable units, and cabinetry for equipping a clinic.',
        'Cosmetic & Whitening Products (منتجات تبييض وتجميل الأسنان)' =>
            'Whitening gels, curing lights, and shade-matching kits for cosmetic dentistry.',
        'Pediatric Dentistry Supplies (مستلزمات طب أسنان الأطفال)' =>
            'Stainless steel crowns, space maintainers, and fluoride treatments for children.',
        'Dental Cements & Bonding Agents (الأسمنت واللواصق السنية)' =>
            'Zinc oxide eugenol, resin cements, and multi-generation bonding agents.',
        'Burs & Cutting Instruments (الفريزات وأدوات القطع)' =>
            'Diamond and carbide burs for cavity preparation and crown reduction.',
        'Sterilization & Infection Control (أجهزة التعقيم ومكافحة العدوى)' =>
            'Autoclaves, ultrasonic cleaners, and sterilization pouches for clinical safety.',
        'Dental Laboratory Equipment (معدات المخبر السني)' =>
            'Casting, trimming, and finishing equipment used in dental laboratories.',
        'Dental Implant Supplies (مستلزمات الزراعة السنية)' =>
            'Implant fixtures, surgical drill kits, and bone graft materials.',
        'Preventive Care Products (منتجات الوقاية السنية)' =>
            'Fluoride varnish, fissure sealants, and patient education preventive kits.',
    ];

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(array_keys(self::CATEGORIES));

        return [
            'name' => $name,
            'description' => self::CATEGORIES[$name],
        ];
    }
}
