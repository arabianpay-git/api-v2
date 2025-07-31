<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionService;
use App\Models\AdsSlider;
use App\Models\Attribute;
use App\Models\Brand;
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
    public function uploado()
    {
        /*
        $users2 = User2::all();
        $encryptionService = new EncryptionService();
        $success = 0;
        foreach ($users2 as $oldUser) {
           $newUser = User::where('email',$encryptionService->db_encrypt($oldUser->email))->first();
           if(!empty($newUser)){
                $newUser->old_id = $oldUser->id;
                $newUser->save();
                $success++;
           }
           
        }
        return $success;
        */
        //return $this->migrateProducts();
        //return $this->migrateBrands();

        $inputList = json_decode('[
  {
    "old_name": "ادوات كهربائية",
    "old_id": "1"
  },
  {
    "old_name": "مستلزمات مكتبية",
    "old_id": "2"
  },
  {
    "old_name": "مشروبات",
    "old_id": "3"
  },
  {
    "old_name": "الأزياء",
    "old_id": "4"
  },
  {
    "old_name": "منتجات غذائية",
    "old_id": "5"
  },
  {
    "old_name": "الفحم والحطب",
    "old_id": "11"
  },
  {
    "old_name": "الزي الموحد",
    "old_id": "21"
  },
  {
    "old_name": "عدد صناعية",
    "old_id": "23"
  },
  {
    "old_name": "رفوف تخزين احمال ثقيلة",
    "old_id": "24"
  },
  {
    "old_name": "رفوف الشبك كروم",
    "old_id": "25"
  },
  {
    "old_name": "رفوف حديد",
    "old_id": "26"
  },
  {
    "old_name": "رفوف خشبية معدنية",
    "old_id": "27"
  },
  {
    "old_name": "رفوف الشبك كروم,سلات وعربات",
    "old_id": "28"
  },
  {
    "old_name": "سلات وعربات",
    "old_id": "29"
  },
  {
    "old_name": "العطور",
    "old_id": "30"
  },
  {
    "old_name": "نسائي",
    "old_id": "31"
  },
  {
    "old_name": "رجالي",
    "old_id": "32"
  },
  {
    "old_name": "للجنسين",
    "old_id": "33"
  },
  {
    "old_name": "عناية",
    "old_id": "34"
  },
  {
    "old_name": "قطع غيار سيارات",
    "old_id": "35"
  },
  {
    "old_name": "مصنع جلود",
    "old_id": "36"
  },
  {
    "old_name": "عبايات",
    "old_id": "37"
  },
  {
    "old_name": "التجميل",
    "old_id": "38"
  },
  {
    "old_name": "المستلزمات الطبية",
    "old_id": "39"
  },
  {
    "old_name": "دعاية وإعلان",
    "old_id": "40"
  },
  {
    "old_name": "الالكترونيات وملحقاتها",
    "old_id": "41"
  },
  {
    "old_name": "الاجهزة الكهربائية",
    "old_id": "42"
  },
  {
    "old_name": "الأثاث والديكور",
    "old_id": "45"
  },
  {
    "old_name": "معدات البناء",
    "old_id": "48"
  },
  {
    "old_name": "أدوات صحية",
    "old_id": "52"
  },
  {
    "old_name": "مستلزمات المقاهي",
    "old_id": "53"
  },
  {
    "old_name": "الآت ومعدات",
    "old_id": "54"
  },
  {
    "old_name": "الطابعات وملحقاتها",
    "old_id": "55"
  },
  {
    "old_name": "مجوهرات",
    "old_id": "56"
  },
  {
    "old_name": "المشاغل",
    "old_id": "57"
  },
  {
    "old_name": "عناية الطفل",
    "old_id": "58"
  },
  {
    "old_name": "عناية البشرة",
    "old_id": "59"
  },
  {
    "old_name": "عناية الشعر",
    "old_id": "60"
  },
  {
    "old_name": "عناية الاسنان",
    "old_id": "61"
  },
  {
    "old_name": "عناية المرأة",
    "old_id": "62"
  },
  {
    "old_name": "عناية الرجل",
    "old_id": "63"
  },
  {
    "old_name": "تجهيزات المشاغل",
    "old_id": "64"
  },
  {
    "old_name": "اجهزة الشعر",
    "old_id": "65"
  },
  {
    "old_name": "الوجه",
    "old_id": "66"
  },
  {
    "old_name": "العيون",
    "old_id": "67"
  },
  {
    "old_name": "عدسات",
    "old_id": "68"
  },
  {
    "old_name": "معطرات الجو",
    "old_id": "69"
  },
  {
    "old_name": "الحواجب",
    "old_id": "70"
  },
  {
    "old_name": "الشفاه",
    "old_id": "71"
  },
  {
    "old_name": "السيارات",
    "old_id": "72"
  },
  {
    "old_name": "طلاء الاظافر",
    "old_id": "73"
  },
  {
    "old_name": "فرش تجميل",
    "old_id": "74"
  },
  {
    "old_name": "اثاث منزلي",
    "old_id": "75"
  },
  {
    "old_name": "التكييف",
    "old_id": "76"
  },
  {
    "old_name": "اثاث مكتبي",
    "old_id": "77"
  },
  {
    "old_name": "اطارات السيارات",
    "old_id": "78"
  },
  {
    "old_name": "اجهزة الغسيل والتجفيف",
    "old_id": "79"
  },
  {
    "old_name": "معلبات",
    "old_id": "80"
  },
  {
    "old_name": "الثلاجات",
    "old_id": "81"
  },
  {
    "old_name": "اكسسوارات السيارات",
    "old_id": "82"
  },
  {
    "old_name": "النشويات",
    "old_id": "83"
  },
  {
    "old_name": "افران",
    "old_id": "84"
  },
  {
    "old_name": "اكسسوارات منزليه",
    "old_id": "85"
  },
  {
    "old_name": "الحدائق والاشجار",
    "old_id": "86"
  },
  {
    "old_name": "اجهزة المطبخ الكهربائية",
    "old_id": "87"
  },
  {
    "old_name": "أشجار طبيعيه",
    "old_id": "88"
  },
  {
    "old_name": "مكواة الملابس",
    "old_id": "90"
  },
  {
    "old_name": "سلاسل",
    "old_id": "92"
  },
  {
    "old_name": "لابتوبات",
    "old_id": "93"
  },
  {
    "old_name": "أساور",
    "old_id": "94"
  },
  {
    "old_name": "كمبيوتر مكتبي",
    "old_id": "95"
  },
  {
    "old_name": "أساور",
    "old_id": "96"
  },
  {
    "old_name": "خواتم",
    "old_id": "97"
  },
  {
    "old_name": "هواتف محمولة",
    "old_id": "98"
  },
  {
    "old_name": "كاميرات المراقبة وملحقاتها",
    "old_id": "99"
  },
  {
    "old_name": "أقراط",
    "old_id": "100"
  },
  {
    "old_name": "البهارات والتوابل",
    "old_id": "101"
  },
  {
    "old_name": "الشاشات",
    "old_id": "102"
  },
  {
    "old_name": "خدمة البوفيهات",
    "old_id": "103"
  },
  {
    "old_name": "الشاي والقهوة",
    "old_id": "104"
  },
  {
    "old_name": "ملحقات الالكترونية",
    "old_id": "105"
  },
  {
    "old_name": "المطبخ",
    "old_id": "106"
  },
  {
    "old_name": "الدجاج واللحوم الطازجة",
    "old_id": "107"
  },
  {
    "old_name": "مستلزمات المطبخ",
    "old_id": "108"
  },
  {
    "old_name": "المصانع",
    "old_id": "109"
  },
  {
    "old_name": "الأطعمة المجمدة",
    "old_id": "111"
  },
  {
    "old_name": "مصنع أخشاب",
    "old_id": "112"
  },
  {
    "old_name": "مصنع مطابخ",
    "old_id": "113"
  },
  {
    "old_name": "المخبوزات",
    "old_id": "114"
  },
  {
    "old_name": "مصانع رخام",
    "old_id": "115"
  },
  {
    "old_name": "الخضار والفواكة",
    "old_id": "116"
  },
  {
    "old_name": "مصنع الشوكولاته",
    "old_id": "117"
  },
  {
    "old_name": "سناكات",
    "old_id": "118"
  },
  {
    "old_name": "حلويات",
    "old_id": "119"
  },
  {
    "old_name": "الورق والبلاستيكيات",
    "old_id": "120"
  },
  {
    "old_name": "المنظفات استهلاكية",
    "old_id": "121"
  },
  {
    "old_name": "الحليب ومشتقاته",
    "old_id": "122"
  },
  {
    "old_name": "المنظفات الطبية",
    "old_id": "123"
  },
  {
    "old_name": "أدوات طبية",
    "old_id": "125"
  },
  {
    "old_name": "المنكهات والإضافات",
    "old_id": "126"
  },
  {
    "old_name": "التمور",
    "old_id": "127"
  },
  {
    "old_name": "مكائن القهوة",
    "old_id": "128"
  },
  {
    "old_name": "أدوات القهوة",
    "old_id": "129"
  },
  {
    "old_name": "مياه",
    "old_id": "130"
  },
  {
    "old_name": "مشروبات غازية",
    "old_id": "131"
  },
  {
    "old_name": "بن القهوة",
    "old_id": "132"
  },
  {
    "old_name": "عصائر طبيعية",
    "old_id": "133"
  },
  {
    "old_name": "قهوة مبردة",
    "old_id": "134"
  },
  {
    "old_name": "عناية الجسم",
    "old_id": "135"
  },
  {
    "old_name": "الزيوت",
    "old_id": "136"
  },
  {
    "old_name": "ساعات",
    "old_id": "137"
  },
  {
    "old_name": "عناية الأظافر",
    "old_id": "138"
  },
  {
    "old_name": "البقوليات",
    "old_id": "139"
  },
  {
    "old_name": "صوصات",
    "old_id": "140"
  },
  {
    "old_name": "مواد بناء",
    "old_id": "142"
  },
  {
    "old_name": "مبيدات",
    "old_id": "143"
  },
  {
    "old_name": "الالبان والاجبان",
    "old_id": "144"
  },
  {
    "old_name": "عصائر",
    "old_id": "145"
  },
  {
    "old_name": "مستلزمات اطفال",
    "old_id": "146"
  },
  {
    "old_name": "رفوف",
    "old_id": "150"
  },
  {
    "old_name": "منتجات مستعملة",
    "old_id": "151"
  },
  {
    "old_name": "مفارش",
    "old_id": "152"
  },
  {
    "old_name": "مواد زراعية",
    "old_id": "153"
  },
  {
    "old_name": "سماد",
    "old_id": "154"
  },
  {
    "old_name": "بذور",
    "old_id": "155"
  },
  {
    "old_name": "مكملات زراعية",
    "old_id": "156"
  },
  {
    "old_name": "مبيدات زراعية",
    "old_id": "157"
  },
  {
    "old_name": "مستلزمات زراعية",
    "old_id": "158"
  },
  {
    "old_name": "غرف النوم",
    "old_id": "159"
  },
  {
    "old_name": "مراتب",
    "old_id": "160"
  },
  {
    "old_name": "سرير مفرد",
    "old_id": "161"
  },
  {
    "old_name": "سرير دورين",
    "old_id": "162"
  },
  {
    "old_name": "دورة مياة",
    "old_id": "164"
  },
  {
    "old_name": "منسوجات دورة المياة",
    "old_id": "165"
  },
  {
    "old_name": "مخدات",
    "old_id": "166"
  },
  {
    "old_name": "مناشف",
    "old_id": "167"
  },
  {
    "old_name": "دعاسات دورة المياة",
    "old_id": "168"
  },
  {
    "old_name": "المستلزمات الاستهلاكية",
    "old_id": "169"
  },
  {
    "old_name": "الأقمشة",
    "old_id": "170"
  },
  {
    "old_name": "اكسسوارات الشعر",
    "old_id": "171"
  },
  {
    "old_name": "المستلزمات المنزلية",
    "old_id": "172"
  },
  {
    "old_name": "المستلزمات الفندقية",
    "old_id": "173"
  },
  {
    "old_name": "عود",
    "old_id": "174"
  },
  {
    "old_name": "دهن العود والمسك",
    "old_id": "175"
  },
  {
    "old_name": "مباخر",
    "old_id": "176"
  },
  {
    "old_name": "اجهزة رذاذ",
    "old_id": "177"
  },
  {
    "old_name": "إضاءة وإنارة",
    "old_id": "178"
  },
  {
    "old_name": "ثريا",
    "old_id": "179"
  },
  {
    "old_name": "إضاءة مكتب",
    "old_id": "180"
  },
  {
    "old_name": "مصابيح",
    "old_id": "181"
  },
  {
    "old_name": "إضاءة الديكور",
    "old_id": "182"
  },
  {
    "old_name": "إضاءة مدمجة",
    "old_id": "183"
  },
  {
    "old_name": "إضاءة ذكية",
    "old_id": "184"
  },
  {
    "old_name": "إضاءة الخارجية",
    "old_id": "185"
  },
  {
    "old_name": "إضاءة الحمام",
    "old_id": "186"
  },
  {
    "old_name": "لمبات LED",
    "old_id": "187"
  },
  {
    "old_name": "كمودينو",
    "old_id": "188"
  },
  {
    "old_name": "دولاب",
    "old_id": "189"
  },
  {
    "old_name": "غرفة أطفال كاملة",
    "old_id": "190"
  },
  {
    "old_name": "كرسي مفرد",
    "old_id": "191"
  },
  {
    "old_name": "تسريحة",
    "old_id": "192"
  },
  {
    "old_name": "سرير كنق",
    "old_id": "193"
  },
  {
    "old_name": "بطاريات",
    "old_id": "194"
  },
  {
    "old_name": "خيوط وأحبال",
    "old_id": "195"
  },
  {
    "old_name": "شموع",
    "old_id": "196"
  },
  {
    "old_name": "خدمات",
    "old_id": "197"
  },
  {
    "old_name": "زيوت عطرية",
    "old_id": "198"
  },
  {
    "old_name": "مستلزمات الحيونات",
    "old_id": "199"
  },
  {
    "old_name": "حقائب ذكية",
    "old_id": "200"
  },
  {
    "old_name": "شنط",
    "old_id": "201"
  }
]');

        $categories = Category::all();

        foreach ($inputList as $item) {
            $oldName = $item->old_name;
            $oldId = $item->old_id;

            $bestMatch = null;
            $bestPercent = 0;
            $bestCategoryId = null;

            foreach ($categories as $category) {
                $categoryName = $category->translations->first()->name ?? '';
                similar_text($oldName, $categoryName, $percent);

                if ($percent > $bestPercent) {
                    $bestPercent = $percent;
                    $bestMatch = $categoryName;
                    $bestCategoryId = $category->id;
                }

                if ($percent > 40) { // حد أدنى لتجنب التطابقات الضعيفة
                    $category->old_id = $oldId;
                    $category->save();
                }
            }

        }
        //return $categories;

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
            'products_order' => []
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
                    'name' => $product->name,
                    'brand' => $product->brand->name ?? 'عام',
                    'thumbnail_image' => !empty($product->thumbnail)?'https://partners.arabianpay.net'.$product->thumbnail:'https://api.arabianpay.net/public/placeholder.jpg',
                    'has_discount' => $discount > 0,
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
                    'name' => $product->name,
                    'brand' => $product->brand->name ?? 'عام',
                    'thumbnail_image' => !empty($product->thumbnail)?'https://partners.arabianpay.net'.$product->thumbnail:'https://api.arabianpay.net/public/placeholder.jpg',
                    'has_discount' => $discount > 0,
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
                    'image' => $slider->image??'https://api.arabianpay.net/public/placeholder.jpg', // get URL by ID
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
                    'image' => $slider->image??'https://api.arabianpay.net/public/placeholder.jpg',  // convert image ID to URL
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
