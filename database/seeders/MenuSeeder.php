<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\Branch; // 👈 Branch model import kiya

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 👇 SAB RESTAURANTS KO FETCH KARENGE
        $restaurants = Restaurant::all();

        // Safety check
        if ($restaurants->isEmpty()) {
            $this->command->error("No restaurant found. Please run RestaurantSetupSeeder first.");
            return;
        }

        // 👇 HAR RESTAURANT KE LIYE LOOP CHALEGA
        foreach ($restaurants as $restaurant) {
            $restaurantId = $restaurant->id;
            $restaurantSlug = $restaurant->slug ?? 'restaurant-' . $restaurantId;

            // ======================
            // 1. Create Master Categories & Items (For Main Restaurant where branch_id is null)
            // ======================
            $this->command->info("Seeding menu for Main Restaurant: {$restaurant->name}...");
            $this->seedMenuData($restaurantId, null, $restaurantSlug);

            // ======================
            // 2. Clone Menu for All Branches of this specific Restaurant
            // ======================
            $branches = Branch::where('restaurant_id', $restaurantId)->get();

            if ($branches->isEmpty()) {
                $this->command->warn("No branches found for {$restaurant->name}. Skipping branch seeding.");
            }

            foreach ($branches as $branch) {
                $this->command->info("Seeding cloned menu for Branch: {$branch->name} (Restaurant: {$restaurant->name})...");
                $this->seedMenuData($restaurantId, $branch->id, $restaurantSlug);
            }
        }

        $this->command->info('Categories and all 100 Menu Items seeded successfully for ALL Restaurants AND all their Branches!');
    }

    /**
     * Helper function to seed data for a specific location (Restaurant or Branch)
     * 👇 Yahan $restaurantSlug add kiya taaki image path dynamically sahi folder mein point kare
     */
    private function seedMenuData($restaurantId, $branchId, $restaurantSlug)
    {
        $categories = [
            ['id' => 1, 'name' => 'SOFT DRINK', 'sort_order' => 1],
            ['id' => 2, 'name' => 'MOCKTAIL', 'sort_order' => 2],
            ['id' => 3, 'name' => 'SOUP', 'sort_order' => 3],
            ['id' => 4, 'name' => 'VEG. STARTER', 'sort_order' => 4],
            ['id' => 5, 'name' => 'PAN. STARTER', 'sort_order' => 5],
            ['id' => 6, 'name' => 'FARSAN', 'sort_order' => 6],
            ['id' => 7, 'name' => 'CHAT', 'sort_order' => 7],
            ['id' => 8, 'name' => 'VEG. SUBJI', 'sort_order' => 8],
            ['id' => 9, 'name' => 'PAN. SUBJI', 'sort_order' => 9],
            ['id' => 10, 'name' => 'KOFTA SUBJI', 'sort_order' => 10],
            ['id' => 11, 'name' => 'KATHOL', 'sort_order' => 11],
            ['id' => 12, 'name' => 'GUJARATI SUBJI', 'sort_order' => 12],
            ['id' => 13, 'name' => 'ROTI', 'sort_order' => 13],
            ['id' => 14, 'name' => 'RICE', 'sort_order' => 14],
            ['id' => 15, 'name' => 'PAPAD', 'sort_order' => 15],
            ['id' => 16, 'name' => 'DAL', 'sort_order' => 16],
            ['id' => 17, 'name' => 'REG.SWEET', 'sort_order' => 17],
            ['id' => 18, 'name' => 'PREMIUM SWEET', 'sort_order' => 18],
            ['id' => 19, 'name' => 'ICE CREAM(Reg)', 'sort_order' => 19],
            ['id' => 20, 'name' => 'ICE CREAM(Pre)', 'sort_order' => 20],
        ];

        // Store mappings of Old Category ID to New Database ID (Crucial for branch linking)
        $categoryMap = [];

        foreach ($categories as $catData) {
            $category = Category::updateOrCreate(
                [
                    'name' => $catData['name'],
                    'restaurant_id' => $restaurantId,
                    'branch_id' => $branchId
                ],
                [
                    'sort_order' => $catData['sort_order'],
                    'is_active' => true,
                ]
            );
            // Save the newly created DB ID for this category
            $categoryMap[$catData['id']] = $category->id;
        }

        // ======================
        // Create Menu Items
        // 👇 Sabhi image paths mein ab dynamic {$restaurantSlug} use hoga
        // ======================
        $menuItems = [
            // SOFT DRINK
            ['category_id' => 1, 'name' => 'Fanta', 'description' => 'Fanta', 'price' => 50.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/soft-drink/fanta.jpg"],
            ['category_id' => 1, 'name' => 'Sprite', 'description' => 'Sprite', 'price' => 50.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/soft-drink/sprite.jpg"],
            ['category_id' => 1, 'name' => 'Pepsi', 'description' => 'Pepsi', 'price' => 50.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/soft-drink/pepsi.jpg"],

            // MOCKTAIL
            ['category_id' => 2, 'name' => 'Mint Mojito', 'description' => 'Mint Mojito', 'price' => 90.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/mocktail/mint-mojito.jpg"],
            ['category_id' => 2, 'name' => 'Pina Colada', 'description' => 'Pina Colada', 'price' => 90.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/mocktail/pina-colada.jpg"],
            ['category_id' => 2, 'name' => 'Fruit Punch', 'description' => 'Fruit Punch', 'price' => 90.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/mocktail/fruit-punch.jpg"],
            ['category_id' => 2, 'name' => 'Blue Lagoon', 'description' => 'Blue Lagoon', 'price' => 90.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/mocktail/blue-lagoon.png"],
            ['category_id' => 2, 'name' => 'Orange Blossom', 'description' => 'Orange Blossom', 'price' => 90.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/mocktail/orange-blossom.jpg"],

            // SOUP
            ['category_id' => 3, 'name' => 'Cream of Tomato', 'description' => 'Cream Of Tomato', 'price' => 80.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/soup/cream-of-tomato.jpg"],
            ['category_id' => 3, 'name' => 'Hot & Sour', 'description' => 'Hot & Sour', 'price' => 80.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/soup/hot-sour.jpg"],
            ['category_id' => 3, 'name' => 'Manchow', 'description' => 'Manchow', 'price' => 80.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/soup/manchow.jpg"],
            ['category_id' => 3, 'name' => 'Lemon Coriendor', 'description' => 'Lemon Coriendor', 'price' => 80.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/soup/lemon-coriendor.jpg"],
            ['category_id' => 3, 'name' => 'Veg. Sweet Corn', 'description' => 'Veg Sweet Corn', 'price' => 80.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/soup/veg-sweet-corn.jpg"],

            // VEG. STARTER
            ['category_id' => 4, 'name' => 'Veg. Lollipop', 'description' => 'Veg Lollipop', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-starter/veg-lollipop.jpg"],
            ['category_id' => 4, 'name' => 'Veg. Hara Bhara Kabab', 'description' => 'Veg Hara Bhara Kabab', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-starter/veg-hara-bhara-kabab.jpg"],
            ['category_id' => 4, 'name' => 'Veg. Spring Roll', 'description' => 'Veg Spring Roll', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-starter/veg-spring-roll.jpg"],
            ['category_id' => 4, 'name' => 'Chinese Cigar Roll', 'description' => 'Chinese Cigar Roll', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-starter/chinese-cigar-roll.jpeg"],
            ['category_id' => 4, 'name' => 'Veg. Crispy', 'description' => 'Veg Crispy', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-starter/veg-crispy.png"],
            ['category_id' => 4, 'name' => 'Veg. Manchurian', 'description' => 'Veg Manchurian', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-starter/veg-manchurian.jpg"],
            ['category_id' => 4, 'name' => 'Corn Tikki', 'description' => 'Corn Tikki', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-starter/corn-tikki.jpg"],

            // PAN. STARTER
            ['category_id' => 5, 'name' => 'Paneer Chilli Dry', 'description' => 'Paneer Chilli Dry', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-starter/paneer-chilli-dry.jpg"],
            ['category_id' => 5, 'name' => 'Panner Manchurian', 'description' => 'Panner Manchurian', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-starter/panner-manchurian.jpg"],
            ['category_id' => 5, 'name' => 'Paneer 65', 'description' => 'Paneer 65', 'price' => 160.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-starter/paneer-65.jpg"],

            // FARSAN
            ['category_id' => 6, 'name' => 'Mix Pakoda', 'description' => 'Mix Pakoda', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/farsan/mix-pakoda.jpg"],
            ['category_id' => 6, 'name' => 'Lilva Kachori', 'description' => 'Lilva Kachori', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/farsan/lilva-kachori.jpg"],
            ['category_id' => 6, 'name' => 'Khandvi', 'description' => 'Khandvi', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/farsan/khandvi.jpg"],
            ['category_id' => 6, 'name' => 'Patra', 'description' => 'Patra', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/farsan/patra.jpg"],
            ['category_id' => 6, 'name' => 'Khaman', 'description' => 'Khaman', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/farsan/khaman.jpg"],
            ['category_id' => 6, 'name' => 'Cutlet', 'description' => 'Cutlet', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/farsan/cutlet.jpg"],
            ['category_id' => 6, 'name' => 'Samosa', 'description' => 'Samosa', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/farsan/samosa.jpg"],

            // CHAT
            ['category_id' => 7, 'name' => 'Pani Puri', 'description' => 'Pani Puri', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/chat/pani-puri.jpg"],
            ['category_id' => 7, 'name' => 'Sev Puri', 'description' => 'Sev Puri', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/chat/sev-puri.jpg"],
            ['category_id' => 7, 'name' => 'Papdi Chat', 'description' => 'Papdi Chat', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/chat/papdi-chat.jpg"],
            ['category_id' => 7, 'name' => 'Basket Chat', 'description' => 'Basket Chat', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/chat/basket-chat.jpg"],
            ['category_id' => 7, 'name' => 'Aloo Tikki Chat', 'description' => 'Aloo Tikki Chat', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/chat/aloo-tikki-chat.jpg"],

            // VEG. SUBJI
            ['category_id' => 8, 'name' => 'Veg. Jaipuri (Brown)', 'description' => 'Veg Jaipuri Brown', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-subji/veg-jaipuri-brown.jpg"],
            ['category_id' => 8, 'name' => 'Veg. Handi', 'description' => 'Veg Handi', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-subji/veg-handi.jpg"],
            ['category_id' => 8, 'name' => 'Kadai (Brown)', 'description' => 'Kadai Brown', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-subji/kadai-brown.jpg"],
            ['category_id' => 8, 'name' => 'Veg. Makhanwala (Red)', 'description' => 'Veg Makhanwala Red', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-subji/veg-makhanwala-red.jpg"],
            ['category_id' => 8, 'name' => 'Veg. Hydrabadi (Green)', 'description' => 'Veg Hydrabadi Green', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-subji/veg-hydrabadi-green.jpg"],
            ['category_id' => 8, 'name' => 'Veg. Kolhapuri (Red)', 'description' => 'Veg Kolhapuri Red', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-subji/veg-kolhapuri-red.jpg"],
            ['category_id' => 8, 'name' => 'Veg. Mughlai (Brown)', 'description' => 'Veg Mughlai Brown', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-subji/veg-mughlai-brown.jpg"],
            ['category_id' => 8, 'name' => 'Veg. Toofani (Red)', 'description' => 'Veg Toofani Red', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/veg-subji/veg-toofani-red.jpg"],

            // PAN. SUBJI
            ['category_id' => 9, 'name' => 'Paneer Tikka Masala (Red)', 'description' => 'Paneer Tikka Masala Red', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-subji/paneer-tikka-masala-red.jpg"],
            ['category_id' => 9, 'name' => 'Paneer Tawa Masala (Red)', 'description' => 'Paneer Tawa Masala Red', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-subji/paneer-tawa-masala-red.jpg"],
            ['category_id' => 9, 'name' => 'Paneer Kadai (Brown)', 'description' => 'Paneer Kadai Brown', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-subji/paneer-kadai-brown.jpg"],
            ['category_id' => 9, 'name' => 'Paneer Butter Masala (Red)', 'description' => 'Paneer Butter Masala Red', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-subji/paneer-butter-masala-red.jpg"],
            ['category_id' => 9, 'name' => 'Paneer Chatpata (Brown)', 'description' => 'Paneer Chatpata Brown', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-subji/paneer-chatpata-brown.jpg"],
            ['category_id' => 9, 'name' => 'Paneer Toofani (Red)', 'description' => 'Paneer Toofani Red', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-subji/paneer-toofani-red.jpg"],
            ['category_id' => 9, 'name' => 'Paneer Handi', 'description' => 'Paneer Handi', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-subji/paneer-handi.jpg"],
            ['category_id' => 9, 'name' => 'Balti (Red)', 'description' => 'Balti Red', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/pan-subji/balti-red.jpg"],

            // KOFTA SUBJI
            ['category_id' => 10, 'name' => 'Malai', 'description' => 'Malai', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/kofta-subji/malai.jpg"],
            ['category_id' => 10, 'name' => 'Kasmiri Kofta (White)', 'description' => 'Kasmiri Kofta White', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/kofta-subji/kasmiri-kofta-white.jpg"],
            ['category_id' => 10, 'name' => 'Veg Kofta (Brown)', 'description' => 'Veg Kofta Brown', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/kofta-subji/veg-kofta-brown.jpg"],
            ['category_id' => 10, 'name' => 'Nargisi Kofta (Green)', 'description' => 'Nargisi Kofta Green', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/kofta-subji/nargisi-kofta-green.jpg"],
            ['category_id' => 10, 'name' => 'Paneer Kofta (Red)', 'description' => 'Paneer Kofta Red', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/kofta-subji/paneer-kofta-red.jpg"],

            // KATHOL
            ['category_id' => 11, 'name' => 'Mug Masala', 'description' => 'Mug Masala', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/kathol/mug-masala.jpg"],
            ['category_id' => 11, 'name' => 'Chana Masala', 'description' => 'Chana Masala', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/kathol/chana-masala.jpg"],

            // GUJARATI SUBJI
            ['category_id' => 12, 'name' => 'Sev Tamate', 'description' => 'Sev Tamate', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/gujarati-subji/sev-tamate.jpg"],
            ['category_id' => 12, 'name' => 'Bhindi Masala', 'description' => 'Bhindi Masala', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/gujarati-subji/bhindi-masala.jpg"],
            ['category_id' => 12, 'name' => 'Lusaniya Bataka', 'description' => 'Lusaniya Bataka', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/gujarati-subji/lusaniya-bataka.jpg"],
            ['category_id' => 12, 'name' => 'Rusawala Bataka Tomato', 'description' => 'Rusawala Bataka Tomato', 'price' => 180.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/gujarati-subji/rusawala-bataka-tomato.jpg"],

            // ROTI
            ['category_id' => 13, 'name' => 'Butter Roti', 'description' => 'Butter Roti', 'price' => 30.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/roti/butter-roti.jpg"],
            ['category_id' => 13, 'name' => 'Butter Naan', 'description' => 'Butter Naan', 'price' => 30.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/roti/butter-naan.jpg"],
            ['category_id' => 13, 'name' => 'Chapati', 'description' => 'Chapati', 'price' => 30.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/roti/chapati.jpg"],
            ['category_id' => 13, 'name' => 'Paratha', 'description' => 'Paratha', 'price' => 30.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/roti/paratha.jpg"],
            ['category_id' => 13, 'name' => 'Butter Kulcha', 'description' => 'Butter Kulcha', 'price' => 30.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/roti/butter-kulcha.jpg"],

            // RICE
            ['category_id' => 14, 'name' => 'Jeera Rice', 'description' => 'Jeera Rice', 'price' => 150.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/rice/jeera-rice.jpg"],
            ['category_id' => 14, 'name' => 'Veg. Pulav', 'description' => 'Veg Pulav', 'price' => 150.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/rice/veg-pulav.jpg"],
            ['category_id' => 14, 'name' => 'Peas Pulav', 'description' => 'Peas Pulav', 'price' => 150.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/rice/peas-pulav.jpg"],
            ['category_id' => 14, 'name' => 'Biryani', 'description' => 'Biryani', 'price' => 150.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/rice/biryani.jpg"],
            ['category_id' => 14, 'name' => 'Veg. Hydrabadi Biryani', 'description' => 'Veg Hydrabadi Biryani', 'price' => 150.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/rice/veg-hydrabadi-biryani.jpg"],
            ['category_id' => 14, 'name' => 'Handi Dum Biryani', 'description' => 'Handi Dum Biryani', 'price' => 150.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/rice/handi-dum-biryani.jpg"],

            // PAPAD
            ['category_id' => 15, 'name' => 'Roasted Papad', 'description' => 'Roasted Papad', 'price' => 40.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/papad/roasted-papad.jpg"],
            ['category_id' => 15, 'name' => 'Fry Papad', 'description' => 'Fry Papad', 'price' => 40.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/papad/fry-papad.jpg"],
            ['category_id' => 15, 'name' => 'Pum Pum Papad', 'description' => 'Pum Pum Papad', 'price' => 40.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/papad/pum-pum-papad.jpg"],

            // DAL
            ['category_id' => 16, 'name' => 'Dal Fry', 'description' => 'Dal Fry', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/dal/dal-fry.jpg"],
            ['category_id' => 16, 'name' => 'Tadka', 'description' => 'Tadka', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/dal/tadka.jpg"],
            ['category_id' => 16, 'name' => 'Dal Palak', 'description' => 'Dal Palak', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/dal/dal-palak.jpg"],

            // REG.SWEET
            ['category_id' => 17, 'name' => 'Gulab Jamun', 'description' => 'Gulab Jamun', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/regsweet/gulab-jamun.jpg"],
            ['category_id' => 17, 'name' => 'Kala Jamun', 'description' => 'Kala Jamun', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/regsweet/kala-jamun.jpg"],
            ['category_id' => 17, 'name' => 'Moong Dal Halwa', 'description' => 'Moong Dal Halwa', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/regsweet/moong-dal-halwa.jpg"],
            ['category_id' => 17, 'name' => 'Gajar Halwa (Seasonal)', 'description' => 'Gajar Halwa Seasonal', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/regsweet/gajar-halwa-seasonal.jpg"],
            ['category_id' => 17, 'name' => 'Dudhi Halwa', 'description' => 'Dudhi Halwa', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/regsweet/dudhi-halwa.jpg"],
            ['category_id' => 17, 'name' => 'Mango Ras (Seasonal)', 'description' => 'Mango Ras Seasonal', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/regsweet/mango-ras-seasonal.jpg"],

            // PREMIUM SWEET
            ['category_id' => 18, 'name' => 'Angoori Basundi', 'description' => 'Angoori Basundi', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/premium-sweet/angoori-basundi.jpg"],
            ['category_id' => 18, 'name' => 'Kesar Pista Basundi', 'description' => 'Kesar Pista Basundi', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/premium-sweet/kesar-pista-basundi.jpg"],
            ['category_id' => 18, 'name' => 'Sitafal  Basundi', 'description' => 'Sitafal  Basundi', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/premium-sweet/sitafal-basundi.jpg"],
            ['category_id' => 18, 'name' => 'Anjeer Basundi', 'description' => 'Anjeer Basundi', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/premium-sweet/anjeer-basundi.jpg"],
            ['category_id' => 18, 'name' => 'Srikhand', 'description' => 'Srikhand', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/premium-sweet/srikhand.jpg"],
            ['category_id' => 18, 'name' => 'Mango Delight (Seasonal)', 'description' => 'Mango Delight Seasonal', 'price' => 120.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/premium-sweet/mango-delight-seasonal.jpg"],

            // ICE CREAM(Reg)
            ['category_id' => 19, 'name' => 'Vanilla', 'description' => 'Vanilla', 'price' => 70.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/ice-creamreg/vanilla.jpg"],
            ['category_id' => 19, 'name' => 'Kaju Draksh', 'description' => 'Kaju Draksh', 'price' => 70.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/ice-creamreg/kaju-draksh.jpg"],
            ['category_id' => 19, 'name' => 'Strawberry', 'description' => 'Strawberry', 'price' => 70.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/ice-creamreg/strawberry.jpg"],
            ['category_id' => 19, 'name' => 'Chocolate', 'description' => 'Chocolate', 'price' => 70.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/ice-creamreg/chocolate.jpg"],
            ['category_id' => 19, 'name' => 'Vanilla With Hot Chocolate', 'description' => 'Vanilla With Hot Chocolate', 'price' => 70.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/ice-creamreg/vanilla-with-hot-chocolate.jpg"],

            // ICE CREAM(Pre)
            ['category_id' => 20, 'name' => 'American Nuts', 'description' => 'American Nuts', 'price' => 100.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/ice-creampre/american-nuts.jpg"],
            ['category_id' => 20, 'name' => 'Butterscotch', 'description' => 'Butterscotch', 'price' => 100.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/ice-creampre/butterscotch.jpg"],
            ['category_id' => 20, 'name' => 'Rajbhog', 'description' => 'Rajbhog', 'price' => 100.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/ice-creampre/rajbhog.jpg"],
            ['category_id' => 20, 'name' => 'Kesar Pista', 'description' => 'Kesar Pista', 'price' => 100.00, 'image_path' => "restaurants/{$restaurantSlug}/Categories/ice-creampre/kesar-pista.jpg"],
        ];

        foreach ($menuItems as $itemData) {
            MenuItem::updateOrCreate(
                [
                    'name' => $itemData['name'],
                    'restaurant_id' => $restaurantId,
                    'branch_id' => $branchId
                ],
                [
                    // Replace the old array category_id with the new actual DB category_id
                    'category_id' => $categoryMap[$itemData['category_id']],
                    'description' => $itemData['description'],
                    'price' => $itemData['price'],
                    'image_path' => $itemData['image_path'],
                    'is_available' => true,
                ]
            );
        }
    }
}