<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionService;
use App\Models\AdsSlider;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\NafathVerification;
use App\Models\Obrand;
use App\Models\Category;
use App\Models\OProduct;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use App\Models\User;
use App\Models\User2;
use Carbon\Carbon;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;

class HomeController extends Controller
{
    /**
     * Parse a date string or return null if empty.
     *
     * @param mixed $date
     * @return string|null
     */
    private function parseDate($value)
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function test() {
        $data = [
                    "verification"  => 'success',  // rejected
                    "phone_number"  => "966566684981",
                    "date"          => date('d-m-Y H:i:s', strtotime(now())) ,
                ];

        $id = "2590153728";
        broadcast(new \App\Events\NafathEvent($data,$id));
        //$validation = NafathVerification::where('national_id', 2417807558)->first();
        //return $validation;
    }

    public function upload()
    {
        //$this->updateBrand();
        $this->migrateShopsToShopSettings();
    }

    private function updateImages(){
      $brands = DB::table('brands')->select('id','logo')->get();

      foreach ($brands as $brand) {
          // تحديث thumbnail
          $logoName = DB::table('uploads')
              ->where('id', $brand->logo)
              ->value('file_name');

          

          DB::table('brands')
              ->where('id', $brand->id)
              ->update([
                  'logo' => $logoName,
              ]);
      }
    }

    public function updateBrand(){
        $obrands = Obrand::all();
        $encryptionService = new EncryptionService();

        foreach($obrands as $ob){
            $brand = Brand::where('name',$encryptionService->db_encrypt($ob->name))->first();
            if(!empty($brand)){
                $brand->old_id = $ob->id;
                $brand->save();
            }
        }
    }

    private function migrateProducts(){
        $oproducts = OProduct::all();

        

        foreach ($oproducts as $op) {
            $category = Category::where('old_id',$op->ocategory_id)->first();
            if(!empty($category)){
                $category_id = $category->id;
            }else{
                $category_id = 104;
            }
            
            $slug = Str::slug($op->name);
            $originalSlug = $slug;
            $counter = 1;

            // Ensure slug is unique
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $user =User::where('old_id',$op->user_id)->first();
            if(!empty($user->id)){
                $user_id = $user->id;
            }else{
                $user_id = 3;
            }

            $brand = Brand::where('old_id',$op->brand_id)->first();

            if(empty($brand)){
                $brand_id = 1;
            }else{
                $brand_id = $brand->id;
            }
            $encryptionService = new EncryptionService();
            $pod = Product::where('name',$encryptionService->db_encrypt($op->name))->where('user_id',$user_id)
            ->where('category_id',$category_id)->first();
            if(empty($pod))
            {
                Product::create([
                'name' => $op->name,
                'slug' => $slug,
                'added_by' => $op->added_by,
                'user_id' => $user_id,
                'category_id' => $category_id,
                'brand_id' => $brand_id,
                'thumbnail' => $op->thumbnail_img,
                'photos' => json_encode([$op->thumbnail_img]), // or explode from $op->photos if CSV
                'tags' => json_encode(explode(',', $op->tags)),
                'variants' => $op->variations ?? '[]',
                'short_description' => null,
                'description' => $op->description,
                'unit_price' => $op->unit_price,
                'purchase_price' => $op->purchase_price,
                'discount' => $op->discount,
                'discount_type' => $op->discount_type,
                'discount_start_date' => $this->convertTimestamp($op->discount_start_date),
                'discount_end_date' => $this->convertTimestamp($op->discount_end_date),
                'published' => $op->published ? 'published' : 'pending',
                'approved' => $op->approved == 1 ? 'approved' : ($op->approved == 0 ? 'pending' : 'rejected'),
                'reason_reject' => $op->reson_reject,
                'featured' => $op->featured,
                'stock_visibility_state' => $op->stock_visibility_state,
                'current_stock' => $op->current_stock,
                'unit' => $op->unit,
                'weight' => $op->weight,
                'min_qty' => $op->min_qty,
                'low_stock_quantity' => $op->low_stock_quantity,
                'tax' => $op->tax,
                'tax_type' => $op->tax_type,
                'shipping_type' => $op->shipping_type,
                'shipping_cost' => $op->shipping_cost,
                'is_quantity_multiplied' => $op->is_quantity_multiplied,
                'est_shipping_days' => $op->est_shipping_days,
                'number_of_sales' => $op->num_of_sale,
                'meta_title' => $op->meta_title,
                'meta_description' => $op->meta_description,
                'meta_img' => $op->meta_img,
                'refundable' => $op->refundable,
                'rating' => $op->rating,
                'views' => $op->views,
                'created_at' => $op->created_at,
                'updated_at' => $op->updated_at,
            ]);
            }
        }

        $this->info("✅ Migrated {$oproducts->count()} products successfully.");
        return $counter;
    }

    private function migrateAttribute(){
      $oattributes = DB::table('oattributes')->get();

      foreach ($oattributes as $oattr) {
          // التأكد من عدم وجود الاسم مسبقًا
          $exists = Attribute::where('name', $oattr->name)->exists();
          if (!$exists && !empty($oattr->name)) {
              Attribute::create([
                  'name' => $oattr->name,
                  'created_at' => $oattr->created_at,
                  'updated_at' => $oattr->updated_at,
              ]);
          }
      }
    }

  function migrateShopsToShopSettings()
  {
      $shops = DB::table('shops')->get();

      foreach ($shops as $shop) {
          // جلب الـ id الجديد من جدول users باستخدام old_id
          $newUserId = DB::table('users')->where('old_id', $shop->user_id)->value('id');
          if (!$newUserId) continue; // إذا لم يتم العثور على مستخدم مطابق، نتخطى

          // التحقق من عدم وجود سجل مرتبط بنفس user_id الجديد
          $exists = ShopSetting::where('user_id', $newUserId)->exists();
          if ($exists) continue;

          ShopSetting::create([
              'user_id'      => $newUserId,
              'name'         => $shop->name ?? '',
              'logo'         => $shop->logo ?? '',
              'sliders'      => json_encode(json_decode($shop->sliders, true) ?? []),
              'banner'       => $shop->top_banner ?? '',
              'phone_number' => $shop->phone ?? '',
              'address'      => $shop->address ?? '',
              'created_at'   => $shop->created_at,
              'updated_at'   => $shop->updated_at,
          ]);
      }

      return "تم نقل البيانات بنجاح وربط shop_settings بالـ user_id الجديد.";
  }

    private function migrateBrands(){
        $obrands = OBrand::all();

        foreach ($obrands as $obrand) {
            $slug = Str::slug($obrand->slug ?? $obrand->name);
            $originalSlug = $slug;
            $counter = 1;

            while (Brand::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            Brand::create([
                'name' => $obrand->name,
                'slug' => $slug,
                'logo' => $obrand->logo ?? '',
                'order_level' => 0,
                'featured' => $obrand->top ? 1 : 0,
                'meta_title' => $obrand->meta_title,
                'meta_description' => Str::limit(strip_tags($obrand->meta_description), 255),
                'created_at' => $obrand->created_at,
                'updated_at' => $obrand->updated_at,
            ]);
        }

        $this->info("✅ Successfully migrated {$obrands->count()} brands.");
        return $counter;
    }

    public function index(): JsonResponse
    {
        $data = [
            'topBrand' => $this->getTopBrands(),
            'featuredCategories' => $this->getFeaturedCategories(),
            'bestSellerProducts' => $this->getBestSellerProducts(),
            'featuredProducts' => $this->getFeaturedProducts(),
            'topShops' => $this->getTopShops(),
            'sliders' => $this->getSliders(),
            'banner0' => [],
            'banner1' => $this->getBanner1(),
            'banner2' => [],
            'products_order' => $this->getFeaturedProducts(),
        ];

        $encryptionService = new EncryptionService();
        $encryptedData = $encryptionService->encrypt($data);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => '',
            'data' => $encryptedData,
        ]);
    }

    protected function getTopBrands()
    {
        return Brand::select('id', 'logo')
        ->with(['translations' => function ($query) {
            $query->where('locale', 'ar'); // or the current locale
        }])
        ->limit(20)
        ->orderBy('id','DESC')
        ->get()
        ->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->translations[0]->name ?? '',
                'logo' => 'https://core.arabianpay.net'.$brand->logo,
            ];
        });
    }

    protected function getFeaturedCategories()
    {
        return Category::with(['children' => function ($query) {
            $query->select('id', 'name', 'icon as image', 'parent_id');
        }])
        ->where('parent_id', null)
        ->select('id', 'name', 'icon as image', 'parent_id')
        ->get()
        ->map(function ($category) {
          if(!empty($category->image)){
            if (Str::startsWith($category->image, '/storage')) {
              $img = 'https://core.arabianpay.net'.$category->image;
            }else{
              $img = 'https://core.arabianpay.net'.$category->image;
            }
          }else{
            $img = 'https://api.arabianpay.net/public/placeholder.jpg';
          }

          
            return [
                'id' => $category->id,
                'name' => $category->name,
                'image' => $img,
                'parent_id' => $category->parent_id,
                'children' => $category->children->map(function ($child) {
                    if(!empty($child->image)){
                      $ch_img = 'https://core.arabianpay.net'.$child->image;
                    }else{
                      $ch_img = 'https://api.arabianpay.net/public/placeholder.jpg';
                    }
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'image' => $ch_img,
                        'parent_id' => $child->parent_id,
                        'children' => [], // Optional: nest deeper if needed
                    ];
                })->toArray(),
            ];
        });
    }


    protected function getBestSellerProducts()
    {
        return Product::select([
                'id',
                'name',
                'thumbnail',
                'discount',
                'discount_type',
                'unit_price as main_price',
                'unit_price as stroked_price',
                'rating',
                'current_stock'
            ])
            ->with(['brand:id,name'])
            ->whereNotNull('name')
            ->where('thumbnail','!=',null)
            ->where('published', 'published')
            ->orderByDesc('number_of_sales')
            ->limit(15)
            ->get()
            ->map(function ($product) {
                $mainPrice = (float)$product->main_price;
                $discount = (float)$product->discount;
                $discountedPrice = $mainPrice;

                if (strtolower($product->discount_type) === 'percent') {
                    $discountedPrice = $mainPrice - ($mainPrice * $discount / 100);
                } elseif (strtolower($product->discount_type) === 'amount') {
                    $discountedPrice = $mainPrice - $discount;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name??'-',
                    'brand' => $product->brand->name ?? 'عام',
                    'thumbnail_image' => !empty($product->thumbnail)?'https://partners.arabianpay.net'.$product->thumbnail:'https://api.arabianpay.net/public/placeholder.jpg',
                    'has_discount' => $discount > 0,
                    'unit' => $product->unit,
                    'discount' => $discount,
                    'discount_type' => $product->discount_type,
                    'stroked_price' => $mainPrice,
                    'main_price' => max($discountedPrice, 0), // prevent negative pricing
                    'rating' => (float)$product->rating,
                    'num_reviews' => 0, // can replace if using reviews
                    'is_wholesale' => false,
                    'currency_symbol' => 'SR',
                    'in_stock' => $product->current_stock > 0,
                ];
            });
    }



    protected function getFeaturedProducts()
    {
        return Product::select([
                'id',
                'name',
                'thumbnail',
                'discount',
                'discount_type',
                'unit_price as main_price',
                'unit_price as stroked_price',
                'rating',
                'current_stock'
            ])
            ->with(['brand:id,name'])
            ->where('published', 'published')
            ->where('featured', 1)
            ->whereNotNull('name')
            ->where('thumbnail','!=',null)
            ->orderByDesc('number_of_sales')
            ->limit(15)
            ->get()
            ->map(function ($product) {
                $mainPrice = (float)$product->main_price;
                $discount = (float)$product->discount;
                $discountedPrice = $mainPrice;

                if (strtolower($product->discount_type) === 'percent') {
                    $discountedPrice = $mainPrice - ($mainPrice * $discount / 100);
                } elseif (strtolower($product->discount_type) === 'amount') {
                    $discountedPrice = $mainPrice - $discount;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name??'-',
                    'brand' => $product->brand->name ?? 'عام',
                    'thumbnail_image' => !empty($product->thumbnail)?'https://partners.arabianpay.net'.$product->thumbnail:'https://api.arabianpay.net/public/placeholder.jpg',
                    'has_discount' => $discount > 0,
                    'discount' => $discount,
                    'unit' => $product->unit,
                    'discount_type' => $product->discount_type,
                    'stroked_price' => $mainPrice,
                    'main_price' => max($discountedPrice, 0), // prevent negative pricing
                    'rating' => (float)$product->rating,
                    'num_reviews' => 0, // can replace if using reviews
                    'is_wholesale' => false,
                    'currency_symbol' => 'SR',
                    'in_stock' => $product->current_stock > 0,
                ];
            });
    }


    protected function getTopShops()
    {
        return ShopSetting::select([
            'id',
            'user_id',
            'name',
            'logo',
        ])
        ->where('name','!=',null)
        ->where('name','!=',"")
        ->limit(10) // No rating field, so limit only
        ->get()
        ->map(function ($shop) {
            return [
                'id' => $shop->id,
                'slug' => Str::slug($shop->name) . '-' . $shop->id, // Dynamic slug
                'user_id' => $shop->user_id,
                'name' => $shop->name,
                'logo' => $shop->logo?'https://partners.arabianpay.net'.$shop->logo:'https://api.arabianpay.net/public/placeholder.jpg',
                'cover' => 'https://api.arabianpay.net/public/placeholder.jpg', // Placeholder cover image
                'rating' => 0, // Since your table has no rating
            ];
        });
    }


    protected function getSliders()
    {

        return AdsSlider::select('image', 'id')->get()
            ->map(function ($slider) {
                return [
                    'image' => $slider->image?'https://core.arabianpay.com'.$slider->image:'https://api.arabianpay.com/uploads/sliders/default_cover.png', // get URL by ID
                    'image_id' => (string) $slider->id,
                    'target' => [
                      'type' => 'brand',
                      'id' => 1,
                      'name' => 'Generic',
                      'image' => $slider->image?'https://core.arabianpay.com'.$slider->image:'https://api.arabianpay.com/uploads/sliders/default_cover.png',
                      'rating' => 0
                  ], // No target data in table
                ];
            });
    }
    

    protected function getBanner1()
    {
        return AdsSlider::select('image', 'id')
            ->get()
            ->map(function ($slider) {
                return [
                    'image' => "https://arabianpay.co//uploads/all/5b5acbc6-c272-41a9-92ea-13bef2fa1a5e.png",
                    'image_id' => (string) $slider->id,
                    'target' => [
                        'type' => 'brand',
                        'id' => 1,
                        'name' => 'Generic',
                        'image' => $slider->image?'https://core.arabianpay.com'.$slider->image:'https://api.arabianpay.com/uploads/sliders/default_cover.png',
                        'rating' => 0
                    ], // No target data in table
                ];
            });
    }


    public function process(Request $request)
    {
        $request->validate([
            'text' => 'required',
            'operation' => 'required|in:encrypt,decrypt',
        ]);

        $encryptionService = new EncryptionService();
        $result = null;

        if ($request->operation === 'encrypt') {
            $result = $encryptionService->encrypt($request->text);
        } else {
            $result = $encryptionService->decrypt($request->text);
        }

        return($result); // Debugging output, remove in production

    }

    protected function resolveTargetType($type)
    {
        return match(class_basename($type)) {
            'Shop' => 'shop',
            'Brand' => 'brand',
            'Category' => 'category',
            default => 'unknown',
        };
    }

    private function convertTimestamp($value)
    {
        return $value ? Carbon::createFromTimestamp($value) : null;
    }
}
