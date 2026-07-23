<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductAvailability;
use App\Enums\ProductCondition;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Real dental equipment/consumable names mapped to a realistic [min, max] price band (USD).
     *
     * @var array<string, array{0: int, 1: int}>
     */
    private const CATALOG = [
        'K-Files Endodontic Set (15-40)' => [80, 220],
        'Rotary NiTi Files System' => [150, 450],
        'Apex Locator' => [200, 1400],
        'Root Canal Obturation Gun' => [60, 180],
        'Gutta Percha Points (Box)' => [8, 25],
        'Paper Points (Box)' => [5, 18],
        'Endo Motor (Rotary Engine)' => [250, 900],
        'Alginate Impression Material' => [15, 55],
        'Addition Silicone Impression Material' => [180, 420],
        'Polyvinyl Siloxane (PVS) Impression Material' => [150, 380],
        'Impression Trays Set' => [20, 90],
        'Impression Compound' => [10, 35],
        'Composite Resin (Universal Shade Kit)' => [30, 120],
        'Dental Amalgam Capsules' => [40, 150],
        'Glass Ionomer Cement' => [20, 70],
        'Bonding Agent (5th Generation)' => [35, 110],
        'Resin Cement (Dual Cure)' => [30, 100],
        'Zinc Oxide Eugenol Cement' => [10, 35],
        'Extraction Forceps (Upper Anterior)' => [45, 140],
        'Extraction Forceps (Lower Molar)' => [45, 140],
        'Elevators Set (Root Tip)' => [35, 120],
        'Periosteal Elevator' => [15, 45],
        'Surgical Curette' => [20, 60],
        'Bone Rongeur Forceps' => [80, 250],
        'Needle Holder' => [25, 80],
        'Surgical Scissors' => [20, 65],
        'Scalpel Handle with Blades' => [10, 35],
        'Dental Chair (Full Package)' => [3500, 15000],
        'Portable Dental Unit' => [1200, 4500],
        'Dental Autoclave Sterilizer' => [900, 5500],
        'Ultrasonic Cleaner' => [150, 700],
        'High-Speed Handpiece' => [180, 650],
        'Low-Speed Handpiece Kit' => [120, 400],
        'Micromotor with Handpiece' => [200, 800],
        'LED Curing Light' => [80, 350],
        'Intraoral X-Ray Sensor' => [3000, 9000],
        'Portable Dental X-Ray Unit' => [2500, 8500],
        'Diagnostic Kit (Mirror, Probe, Tweezers)' => [10, 35],
        'Periodontal Probe' => [8, 25],
        'Scaler Tips (Ultrasonic)' => [15, 60],
        'Hand Scaling Instruments Set' => [40, 130],
        'Root Planing Curettes Set' => [50, 160],
        'Local Anesthetic Cartridges (Lidocaine 2%)' => [10, 35],
        'Dental Syringe (Aspirating)' => [8, 25],
        'Disposable Needles (Box of 100)' => [5, 15],
        'Nitrile Examination Gloves (Box)' => [4, 12],
        'Surgical Face Masks (Box of 50)' => [3, 10],
        'Disposable Bibs (Pack)' => [4, 12],
        'Cotton Rolls (Bag)' => [2, 8],
        'Saliva Ejectors (Pack)' => [3, 10],
        'Whitening Gel Kit (Carbamide Peroxide)' => [30, 120],
        'Teeth Whitening LED Lamp' => [90, 300],
        'Orthodontic Brackets (Metal Set)' => [25, 90],
        'Orthodontic Wires (NiTi Arch)' => [10, 35],
        'Elastic Ligatures (Pack)' => [3, 10],
        'Pediatric Stainless Steel Crowns Kit' => [40, 130],
        'Fluoride Varnish' => [15, 45],
        'Fissure Sealant Kit' => [25, 80],
        'Space Maintainer Kit' => [30, 90],
        'Dental Burs Set (Diamond)' => [20, 70],
        'Carbide Burs Set' => [15, 55],
        'Rubber Dam Kit' => [20, 65],
        'Articulating Paper (Pack)' => [3, 10],
        'Dental Wax (Sheets)' => [5, 20],
        'Denture Base Acrylic Resin' => [25, 90],
        'Crown and Bridge Temporary Material' => [20, 70],
        'Dental Implant Fixture Kit' => [150, 600],
        'Implant Surgical Drill Kit' => [400, 1500],
        'Bone Graft Material' => [80, 350],
        'Dental Loupes (Magnification 3.5x)' => [150, 600],
        'LED Dental Headlight' => [80, 300],
    ];

    private const BRANDS = [
        'Dentsply Sirona',
        '3M ESPE',
        'Ivoclar Vivadent',
        'Kerr Dental',
        'GC America',
        'Nobel Biocare',
        'Mani Inc.',
        'Woodpecker Medical',
        'Hu-Friedy',
        'Zhermack',
        'Cavex Holland',
        'Ultradent Products',
        'Coltene',
        'VOCO GmbH',
        'SDI Limited',
        'Septodont',
        'KaVo Dental',
        'NSK Dental',
        'W&H Dentalwerk',
        'B.Braun',
    ];

    private const USE_CASES = [
        'root canal therapy',
        'cavity restoration and fillings',
        'oral and maxillofacial surgery',
        'fixed and removable prosthodontics',
        'routine diagnostic check-ups',
        'orthodontic treatment and alignment',
        'pediatric dental care',
        'periodontal maintenance and scaling',
        'cosmetic and whitening procedures',
        'dental implant placement',
        'infection control and clinical sterilization',
        'preventive dental care',
    ];

    public function definition(): array
    {
        $name = $this->faker->randomElement(array_keys(self::CATALOG));
        [$min, $max] = self::CATALOG[$name];
        $brand = $this->faker->randomElement(self::BRANDS);
        $condition = $this->faker->randomElement($this->weightedConditions());

        return [
            'store_id' => User::factory()->asStoreOwner(),
            'category_id' => Category::factory(),
            'name' => $name,
            'description' => $this->buildDescription($name, $brand, $condition),
            'price' => $this->faker->randomFloat(2, $min, $max),
            'brand' => $brand,
            'availability_status' => $this->faker->randomElement($this->weightedAvailability()),
            'condition' => $condition,
        ];
    }

    public function newCondition(): static
    {
        return $this->state(fn(): array => ['condition' => ProductCondition::NEW]);
    }

    public function used(): static
    {
        return $this->state(fn(): array => ['condition' => ProductCondition::USED]);
    }

    public function available(): static
    {
        return $this->state(fn(): array => ['availability_status' => ProductAvailability::AVAILABLE]);
    }

    public function limited(): static
    {
        return $this->state(fn(): array => ['availability_status' => ProductAvailability::LIMITED]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn(): array => ['availability_status' => ProductAvailability::OUT_OF_STOCK]);
    }

    private function buildDescription(string $name, string $brand, ProductCondition $condition): string
    {
        $useCase = $this->faker->randomElement(self::USE_CASES);

        $conditionNote = $condition === ProductCondition::USED
            ? 'Pre-owned and functionally tested; shows light signs of use.'
            : 'Brand new, unopened, and sealed in original packaging.';

        return "{$name} by {$brand}, commonly used for {$useCase}. {$conditionNote}";
    }

    /**
     * @return array<int, ProductCondition>
     */
    private function weightedConditions(): array
    {
        return [
            ...array_fill(0, 75, ProductCondition::NEW),
            ...array_fill(0, 25, ProductCondition::USED),
        ];
    }

    /**
     * @return array<int, ProductAvailability>
     */
    private function weightedAvailability(): array
    {
        return [
            ...array_fill(0, 70, ProductAvailability::AVAILABLE),
            ...array_fill(0, 25, ProductAvailability::LIMITED),
            ...array_fill(0, 5, ProductAvailability::OUT_OF_STOCK),
        ];
    }
}
