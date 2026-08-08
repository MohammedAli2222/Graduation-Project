<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 *
 * The dental supplies taxonomy is a fixed, real-world domain rather than
 * infinite fake data, so this factory deliberately caps out at 50 unique,
 * curated categories. Requesting more than 50 will exhaust Faker's
 * unique() pool by design.
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
        'Dental Implant Surgical Kits (أطقم جراحة الزراعة)' =>
            'Complete surgical kits for implant placement, including drills and torque wrenches.',
        'Sedation & Nitrous Oxide Equipment (معدات التخدير بالغاز الضاحك)' =>
            'Inhalation sedation units, nasal hoods, and nitrous oxide/oxygen delivery systems.',
        'TMJ & Occlusion Diagnostic Tools (أدوات تشخيص المفصل الفكي والإطباق)' =>
            'Articulators, facebows, and occlusal analysis devices for TMJ assessment.',
        'Digital Scanning & CAD/CAM Equipment (معدات المسح الرقمي وتصنيع الحاسوب)' =>
            'Intraoral scanners, milling units, and digital design software licenses.',
        'Endodontic Irrigation Systems (أنظمة الري اللبي)' =>
            'Irrigation needles, sonic activators, and sodium hypochlorite delivery systems.',
        'Matrix Systems & Wedges (أنظمة الحواجز السنية والأسافين)' =>
            'Sectional matrix bands, rings, and wooden/plastic wedges for proximal restorations.',
        'Shade Guides & Color Matching (أدلة تظليل الأسنان)' =>
            'VITA shade guides and digital shade-matching devices for prosthetic work.',
        'Retraction Cord & Hemostatic Agents (خيوط التنحية ومواد وقف النزف)' =>
            'Gingival retraction cords, astringent gels, and hemostatic solutions.',
        'Rubber Dam & Isolation Systems (السد المطاطي وأنظمة العزل)' =>
            'Rubber dam sheets, clamps, frames, and isolation systems for moisture control.',
        'Dental Loupes & Magnification (عدسات التكبير الطبية)' =>
            'Surgical loupes, magnification headbands, and LED headlights for clinical precision.',
        'Waste Management & Sharps Disposal (إدارة النفايات الطبية والأدوات الحادة)' =>
            'Sharps containers, amalgam separators, and clinical waste disposal supplies.',
        'Denture Repair & Relining Materials (مواد إصلاح وتبطين الأطقم)' =>
            'Chairside reline materials, repair resins, and denture adhesive kits.',
        'Bleaching & In-Office Whitening Systems (أنظمة التبييض داخل العيادة)' =>
            'Professional in-office whitening kits with high-concentration peroxide gels.',
        'Sleep Apnea & Snoring Devices (أجهزة انقطاع النفس النومي والشخير)' =>
            'Mandibular advancement devices and oral appliances for sleep-disordered breathing.',
        'Dental Photography Equipment (معدات التصوير السني)' =>
            'Intraoral cameras, photographic mirrors, and retractors for clinical documentation.',
        'Air & Water Syringe Supplies (مستلزمات محاقن الهواء والماء)' =>
            'Syringe tips, tubing, and replacement parts for dental unit air/water syringes.',
        'Suction & Evacuation Systems (أنظمة الشفط والإخلاء)' =>
            'High-volume evacuator tips, suction hoses, and central vacuum system components.',
        'Post & Core Build-Up Systems (أنظمة الدعامات وإعادة بناء التاج)' =>
            'Fiber posts, core build-up composites, and post cementation kits.',
        'Occlusal Splints & Night Guards (الواقيات الليلية وأجهزة الإطباق)' =>
            'Custom night guards, bruxism splints, and thermoplastic sheets.',
        'Dental Practice Software & IT (برمجيات وتقنية إدارة العيادات)' =>
            'Practice management licenses, imaging software, and clinic IT accessories.',
        'Orthodontic Retainers & Aligners (المثبتات التقويمية والتقويم الشفاف)' =>
            'Clear aligner materials, retainer wire, and thermoforming sheets.',
        'Local Hemostasis & Suturing Supplies (مستلزمات الخياطة ووقف النزف الموضعي)' =>
            'Suture materials, needle drivers, and surgical hemostatic sponges.',
        'Dental Unit Waterline Treatment (معالجة خطوط مياه وحدة الأسنان)' =>
            'Waterline disinfection tablets, biofilm treatment, and testing strips.',
        'Bite Registration Materials (مواد تسجيل الإطباق)' =>
            'PVS and wax bite registration materials for accurate occlusal records.',
        'Dental Lab Articulating & Mounting Supplies (مستلزمات تركيب المخبر)' =>
            'Mounting plaster, articulator accessories, and lab bench consumables.',
        'Ergonomic Clinic Accessories (مستلزمات العيادة المريحة)' =>
            'Ergonomic stools, wrist supports, and posture accessories for clinical staff.',
        'Pit & Fissure Sealant Systems (أنظمة عزل الحفر والشقوق)' =>
            'Light-cure sealant materials and applicator tips for preventive care.',
        'Denture Base & Try-In Materials (مواد قاعدة الأطقم والتجربة)' =>
            'Baseplate wax, try-in resins, and denture teeth sets.',
        'Dental Radiography Film & Barriers (أفلام الأشعة وحواجز الوقاية)' =>
            'Barrier sleeves, sensor covers, and disposable radiographic film holders.',
        'Handpiece Maintenance & Lubrication (صيانة وتشحيم القطع اليدوية)' =>
            'Handpiece oil, cleaning cassettes, and maintenance kits for rotary instruments.',
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
