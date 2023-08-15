<?php

namespace App;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use App\Models\TranslateAwareModel;
use App\Models\Variants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class Items extends TranslateAwareModel
{
    use SoftDeletes;
    public $translatable = ['name', 'description'];

    protected $table = 'items';
    protected $appends = ['logom', 'icon', 'short_description'];
    protected $fillable = ['name', 'description', 'image', 'price','discounted_price', 'category_id', 'vat','enable_system_variants'];
    protected $imagePath = 'storage/uploads/images/';

    protected function getImge()
    {
        if ($this->image) {
            $path = storage_path("app/public/uploads/images/$this->image");
            $exists = File::exists($path);
            if (!$exists) {
                return asset('storage/null.jpg');
            }

            $image = Image::make($path);

            $response = Response::make($image->encode($image->mime), 200);
            $response->header("CF-Cache-Status", 'HIF');
            $response->header("Cache-Control", 'max-age=604800, public');
            $response->header("Content-Type", $image->mime);

            // Generate a URL for the image
            return asset("storage/uploads/images/$this->image");
        }
        return asset('storage/null.jpg');
    }

    public function substrwords($text, $chars, $end = '...')
    {
        if (strlen($text) > $chars && strpos($text, " ") !== false) {
            $text = $text.' ';
            $text = substr($text, 0, $chars);
            $text = substr($text, 0, strrpos($text, ' '));
            $text = $text.'...';
        }

        return $text;
    }

    public function getLogomAttribute()
    {
        return $this->getImge();
    }

    public function getIconAttribute()
    {
        return $this->getImge();
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            $path = storage_path("app/public/uploads/images/$this->image");
            $exists = File::exists($path);
            if (!$exists) {
                return asset('storage/null.jpg');
            }

            $image = Image::make($path);

            $response = Response::make($image->encode($image->mime), 200);
            $response->header("CF-Cache-Status", 'HIF');
            $response->header("Cache-Control", 'max-age=604800, public');
            $response->header("Content-Type", $image->mime);

            // Generate a URL for the image
            return asset("storage/uploads/images/$this->image");
        }
        return asset('storage/null.jpg');
    }

    public function getItempriceAttribute()
    {
        return  Money($this->price, config('settings.cashier_currency'), config('settings.do_convertion'))->format();
    }

    public function getShortDescriptionAttribute()
    {
        return  $this->substrwords($this->description, config('settings.chars_in_menu_list'));
    }

    public function category()
    {
        return $this->belongsTo(\App\Categories::class);
    }

    public function extras()
    {
        return $this->hasMany(\App\Extras::class, 'item_id', 'id');
    }

    public function options()
    {
        return $this->hasMany(\App\Models\Options::class, 'item_id', 'id');
    }

    public function variants()
    {
        return $this->hasMany(\App\Models\Variants::class, 'item_id', 'id')->whereNull('deleted_at');;
    }

    public function allergens()
    {
        return $this->belongsToMany(\App\Models\Allergens::class,'item_has_allergens','item_id', 'allergen_id');
    }


    public function systemvariants()
    {
        return $this->hasMany(\App\Models\Variants::class, 'item_id', 'id')->where('variants.is_system',1)->whereNull('deleted_at');
    }

    public function uservariants()
    {
        return $this->hasMany(\App\Models\Variants::class, 'item_id', 'id')->where('variants.is_system',0)->whereNull('deleted_at');;
    }

    public function makeAllMissingVariants($itemPrice){
        //At this moment, all system variables, should be removed

        //The idea is to go over all the options to create the matrix
        $optionsMatrix=[];
        foreach ($this->options as $key => $option) {
            $optionsMatrix[$option->id]=explode(',', $option->options);
            foreach ($optionsMatrix[$option->id] as $key => &$value) {
                $value=["op_id"=>$option->id,"value"=>Str::slug($value, '-'),'data'=>[]];
            }
        }

        //Regular array
        $regular=[];
        foreach ($optionsMatrix as $key => $valuer) {
            array_push($regular,$valuer);
        }
        for ($i=sizeof($regular)-1; $i>0 ; $i--) {
           foreach ($regular[$i-1] as $key => &$valueSE) {
                $valueSE['data']=$regular[$i];
           }
        }

        //Ok, now we have the matrix -
       // print_r($regular);
        $strings=[];
        if(sizeof($regular)>0){
            foreach ($regular[0] as $key => $valueM) {
                $current=$this->converterKV($valueM);
                if(count($valueM['data'])==0){
                    array_push($strings,"{".$current."}");
                }else{
                    foreach ($valueM['data'] as $key => $valueL) {
                        $secondCurrent=$current.",".$this->converterKV($valueL);
                        if(count($valueL['data'])==0){
                            array_push($strings,"{".$secondCurrent."}");
                        }else{
                            foreach ($valueL['data'] as $key => $valueK) {
                                $thirdCurrent=$secondCurrent.",".$this->converterKV($valueK);
                                if(count($valueK['data'])==0){
                                    array_push($strings,"{".$thirdCurrent."}");
                                }else{
                                    foreach ($valueK['data'] as $key => $valueJ) {
                                        $forthCurrent=$thirdCurrent.",".$this->converterKV($valueJ);
                                        if(count($valueJ['data'])==0){
                                            array_push($strings,"{".$forthCurrent."}");
                                        }else{
                                            foreach ($valueJ['data'] as $key => $valuH) {
                                                $fifthCurrent=$forthCurrent.",".$this->converterKV($valuH);
                                                if(count($valuH['data'])==0){
                                                    array_push($strings,"{".$fifthCurrent."}");
                                                }else{
                                                    foreach ($valuH['data'] as $key => $valuP) {
                                                        $sixtCurrent=$fifthCurrent.",".$this->converterKV($valuP);
                                                        if(count($valuP['data'])==0){
                                                            array_push($strings,"{".$sixtCurrent."}");
                                                        }else{
                                                            foreach ($valuP['data'] as $key => $valuO) {
                                                                $seventCurrent=$sixtCurrent.",".$this->converterKV($valuO);
                                                                if(count($valuO['data'])==0){
                                                                    array_push($strings,"{".$seventCurrent."}");
                                                                }else{
                                                                    foreach ($valuO['data'] as $key => $valuQ) {
                                                                        $eightCurrent=$seventCurrent.",".$this->converterKV($valuQ);
                                                                        if(count($valuQ['data'])==0){
                                                                            array_push($strings,"{".$eightCurrent."}");
                                                                        }else{
                                                                            foreach ($valuQ['data'] as $key => $valuW) {
                                                                                $nineCurrent=$eightCurrent.",".$this->converterKV($valuW);
                                                                                if(count($valuW['data'])==0){
                                                                                    array_push($strings,"{".$nineCurrent."}");
                                                                                }else{
                                                                                    foreach ($valuW['data'] as $key => $valueC) {
                                                                                        $tenthCurrent=$nineCurrent.",".$this->converterKV($valueC);
                                                                                        if(count($valueC['data'])==0){
                                                                                            array_push($strings,"{".$tenthCurrent."}");
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }

                                                                    }
                                                                }
                                                            }
                                                        }

                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }



        //Now for each variant, l
        foreach ($this->uservariants as $key => $variant) {
            if (($key = array_search($variant->options, $strings)) !== false) {
                unset($strings[$key]);
            }
        }

        //Add the missing varaints
        foreach ($strings as $key => $value) {
            $variant = Variants::create([
                'price'=>$itemPrice,
                'item_id'=>$this->id,
                'options'=>$value,
                'is_system'=>1,
            ]);
            $variant->save();
        }
    }

    private function converterKV($value){
        return "\"".$value['op_id']."\"".":"."\"".$value['value']."\"";
    }


    public static function boot()
    {
        parent::boot();
        self::deleting(function ($model) {
            if ($model->isForceDeleting()) {


                //Delete Options
                $model->options()->forceDelete();

                //Deletee Variants
                foreach ($model->variants()->get() as $key => $variant) {
                    $variant->extras()->detach();
                }
                $model->variants()->forceDelete();

                //Delete extras
                $model->extras()->forceDelete();
            }

            return true;
        });
    }
}
