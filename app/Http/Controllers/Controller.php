<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function validationTranslation()
    {
        # code...
        return [
            'car_id.required' => 'نمبر موتر ضروری میباشد!',
            'car_id.exists' => 'نمبر موتر در سیستم موجود نیست!',
            'order_id.required' => 'نمبر سفارش ضروری میباشد!',
            'order_id.exists' => 'نمبر سفارش در سیستم موجود نیست!',
            "created_at.required" => "تاریخ ثبت ضروری میباشد",
            "created_at.date" => "تاریخ ثبت درست نمی باشد",
            "created_at.before_or_equal" => "تاریخ ثبت بزرگتر از تاریخ فعلی شده نمیتواند!",
            'car_id.exists' => 'نمبر موتر در سیستم موجود نیست!',
            "date.required" => "تاریخ ثبت ضروری میباشد",
            "date.before_or_equal" => "تاریخ ثبت بزرگتر از تاریخ فعلی شده نمیتواند!",
            'name.required' => 'نام ضروری میباشد',
            'name.min' => 'نام کمتر از سه شده نیتواند',
            'type.required' => 'نوعیت ضروری میباشد',
            'customer_name.required' => 'اسم مشتری ضروری میباشد',
            'customer_phone.required' => 'شماره تماس مشتری ضروری میباشد',
            'count.required' => 'تعداد ضروری میباشد ',
            'count.numeric' => 'تعداد باید عدد باشد',
            'count.min' => 'تعداد کمتر از یک شده نیتواند',
            'receiver_name.required' => 'اسم گیرنده ضروری میباشد',
            'receiver_phone.required' => 'شماره تماس گیرنده ضروری میباشد',
            'country.required' => ' کشور ضروری میباشد',
            'city.required' => ' شهر ضروری میباشد',
            'address.required' => 'آدرس ضروری می باشد',
            'price_per_killo.required' => 'قیمت فی کیلو ضروری میباشد ',
            'price_per_killo.numeric' => 'قیمت فی کیلو باید عدد باشد',
            'price_per_killo.min' => 'قیمت فی کیلو کمتر از یک شده نیتواند',
            'price.required' => 'قیمت ضروری میباشد ',
            'price.numeric' => 'قیمت باید عدد باشد',
            'price.min' => 'قیمت کمتر از یک شده نیتواند',
            'weight.required' => 'وزن ضروری میباشد ',
            'weight.numeric' => 'وزن باید عدد باشد',
            'weight.min' => 'وزن کمتر از یک شده نیتواند',
            'paid_amount.numeric' => 'مقدار پرداختی باید عدد باشد',
            'paid_amount.min' => 'مقدار پرداختی کمتر از یک شده نمی تواند',
            'items.required' => 'موارد ضروری می باشد',
            'items.array' => 'موارد باید لیست باشد',
            'items.min' => 'طول لیست موارد کمتر از یک شده نمی تواند',
            'expense.required' => 'هزینه ضروری می باشد',
            'expense.array' => 'هزینه باید لیست باشد',
            'expense.min' => 'طول لیست هزینه کمتر از یک شده نمی تواند',
            'expense.*.name.required' => 'نام هزینه ضروری می باشد',
            'expense.*.price.required' => 'قیمت هزینه ضروری می باشد',
            'expense.*.price.integer' => 'قیمت هزینه باید عدد باشد',
            'expense.*.price.min' => 'قیمت هزینه کمتر از یک شده نمی تواند',
            'amount.required' => "مقدار ضروری می باشد",
            'amount.min' => "مقدار از کمتر از یک شده نمی تواند",
            'items.*.name.required' => 'نام در موارید ضرور می باشد',
            'items.*.count.required' => 'شمارش در موارید ضرور می باشد',
            'items.*.count.numeric' => 'شمارش در موارید باید عدد باشد',
            'items.*.count.min' => 'شمارش در موارید از یک کمتر بوده نمی تواند',
            'items.*.type.required' => 'نوعیت در موارید ضروری می باشد',
            'items.*.weight.required' => 'وزن در موارید ضرور می باشد',
            'extra_expense.sometimes' => 'مصرف اضافه',
            'extra_expense.array' => 'مصرف اضافه باید لیست باشد',
            'extra_expense.*.name.required_with' => 'نام در مصرف اضافه ضرور می باشد',
            'extra_expense.*.name.string' => 'نام در مصرف اضافه باید کلمه باشد',
            'extra_expense.*.price.required_with' => 'قمیت در مصرف اضافه ضرور می باشد',
            'extra_expense.*.price.numeric' => 'قیمت در مصرف اضافه باید عدد باشد',
            'extra_expense.*.price.min' => 'قیمت در مصرف اضافه کمتر از یک شده نمی تواند',
        ];
    }

    public function search($query, $request, $columns)
    {

        $searchBy = $request->searchBy;
        $search = $request->search;
        if ($searchBy && $search != '') {
            if ($searchBy == 'all') {
                foreach ($columns as $key => $value) {
                    $variables = explode('.', $value);
                    if (count($variables) > 1) {
                        if ($key == 0) {
                            $query =   $query->whereHas($variables[0], function ($q) use ($variables, $search) {
                                return $q->where($variables[1], 'LIKE', '%' . $search . '%');
                            });
                        } else {

                            $query =   $query->orWhereHas($variables[0], function ($q) use ($variables, $search) {
                                return $q->where($variables[1], 'LIKE', '%' . $search . '%');
                            });
                        }
                    } else {
                        if ($key == 0)
                            $query =  $query->where($value, 'LIKE', '%' . $search . '%');
                        else
                            $query =  $query->orWhere($value, 'LIKE', '%' . $search . '%');
                    }
                }
            } else {
                if ($searchBy == 'created_at') {
                    $query =  $query->where('created_at', 'LIKE', '%' . $search . '%');
                }

                $query =   $query->where($searchBy, $search);
            }
        }

        return $query;
    }
}
