<?php

namespace App\Http\Controllers\User;

use App\Http\Requests;
use Illuminate\Support\Facades\Request;   //替代4.2的Input
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{

	public function anyIndex(){

		//获取路由传入的参数
		//echo Route::input('name');

		//获取配置信息
		$value = config('app.timezone');
		//var_dump($value);


		//获取请求输入  http://host-8/web/user?name=dfse  输出：dfse
		$name1 = Request::has('name') ? Request::get('name') : '';

		//取得特定输入数据，若没有则取得默认值
		$name2 = Request::input('name','默认值');

		$input = Request::all();   //所有的

		$input = Request::input('products.0.name');

		//重定向
		//return redirect('login');

		//获取cookie
		$value = Request::cookie('name');

		//获取域名
		$url = Request::root();
//		echo $url;

		$data=[
			'url'      => $url,
			'name1'    => $name1,
			'name2'    => $name2,
			'value'    => $value
		];
		//响应视图   响应最常用的几个方法：make/view/json/download/redirectTo
		return response()->view('user.user',$data);
	}
}
