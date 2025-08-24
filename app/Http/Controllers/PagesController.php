<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    use ApiResponseTrait;
   
    public function getTermsConditions(): JsonResponse
    {
        // Assuming you have a TermsConditions model or similar to fetch the content
        
        $terms = `<p><b>شروط وأحكام المستخدم&nbsp;<br>
                </b></p><p>1. 1 تشكل هذه الشروط والأحكام اتفاقية مُلزِمَة نظامًا بين
                (إما شخصيًا أو نيابة عن كيان تمثلونه) شركة أرابيان باي&nbsp; 
                المالية ذ.م.م. (\"أرابيان باي\")، ويشار إليها فيما يلي باسم \"نحن\"، فيما يتعلق بجميع جوانب خدمات أرابيان باي و استخدامك لتطبيق الجوال أو وصولك إلى موقعنا على الويب، بالإضافة إلى أي قناة وسائط أو موقع ويب أو تطبيق جوال متعلق أو مرتبط به ومن خلال وصولك إلى واستخدامك لتطبيق الجوال أو موقع الويب. أنتم تُقِرّون بأنكم قد قرأت وفهمت ووافقت على الالتزام بهذه الشروط والأحكام، بصيغتها المعدلة من وقت لآخر. إذا كنت لا توافق على هذه الشروط والأحكام، فعليك التوقف عن استخدام أو الوصول إلى تطبيق الجوال أو موقع الويب على الفور. إذا كنت تستخدم تطبيق الجوال أو موقع الويب نيابة عن الغير بما في ذلك، على سبيل المثال لا الحصر، أي كيان تجاري، 
                فأنت تتعهد بأنك مفوض ولديك سلطة إلزام الغير بهذه الشروط والأحكام.
                &nbsp;</p><p><br></p><p>1.2 لا يعد التاجر الذي تشتري منه السلع أو الخدمات باستخدام خدمات أرابيان باي طرفًا في هذه الشروط والأحكام وأي شروط سارية بينكم 
                وبين التاجر مستقلة عن هذه الشروط والأحكام.</p>`; 
      

                           //$terms = 'Terms and conditions content goes here.'; // Replace with actual data retrieval logic

                           $data = ['title'=>'شروط وأحكام المستخدم','content' => $terms];
        return $this->returnData($data, 'Terms and conditions retrieved successfully.');
    }

    public function getReplacementPolicy(): JsonResponse
    {
        // Assuming you have a ReplacementPolicy model or similar to fetch the content
        $policy = 'Replacement policy content goes here.'; // Replace with actual data retrieval logic

        return $this->returnData(['title'=>'replacement policy','content' => $policy], 'Replacement policy retrieved successfully.');
    }

}