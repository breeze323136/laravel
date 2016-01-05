# Laravel PHP Framework 5.\*.*  Readme.md

  @(form:http://www.sexyphp.com   Laravel5.*的入门学习)

###1 第一次安装执行
    php composer.phpr dump-autoload


###2 5.2 版本新增Models目录：
	php artisan make:model Models


###3 创建控制器和模型：

    php artisan make:controller Admin/AdminHomeController

    php artisan make:model Models/User/UserModel


###4 定义路由组

	//路由前缀 prefix
	Route::group(['prefix' => 'web',], function() {

	    Route::get('/', function () {
	        return view('welcome');
	    });
	    //  Route::get('/user/{name}', 'User\UserController@index');
	    Route::controller('/user', 'User\UserController'); //单一路由
	});

	//命名空间 namespace      中间件 middleware
	Route::group(['prefix' => 'admin', 'namespace' => 'Admin','middleware' => 'auth'], function(){
	    Route::get('/', 'AdminHomeController@index');
	});

	//访问路径   http://domain/web/user      http://domain/admin

###5 控制器获取路由传入的参数
    use Illuminate\Support\Facades\Route;

    echo Route::input('name');


###6 获取配置信息
    $value = config('app.timezone');

    //or

    use Illuminate\Support\Facades\Config;

    $bucket   = Config::get('app.qiniu_backup2');


###7 HTTP请求与输入  Lravel4.2用Input  5.2用 Request替代4.2的Input

    use Illuminate\Support\Facades\Request;

    //获取请求输入  http://host-8/web/user?name=dfse  输出：dfse
    $name1 = Request::has('name') ? Request::get('name') : '';

    //取得特定输入数据，若没有则取得默认值
    $name2 = Request::input('name','默认值');

    $input = Request::all();   //所有的

    $input = Request::input('products.0.name');


###8 HTTP响应：报文、Cookie、视图、重定向、宏  Response有很多实用很常用的方法，具体看  \Illuminate\Contracts\Routing\ResponseFactory

    use Illuminate\Support\Facades\Response;

    //$content 响应主体    $status  状态   $value  报头
    return response($content, $status)
                  ->header('Content-Type', $value);

    $data=[
   			'url'      => $url,
   			'name1'    => $name1,
   			'name2'    => $name2,
   			'value'    => $value
    ];
    //响应视图   响应最常用的几个方法：make/view/json/download/redirectTo
    return response()->view('user.user',$data);


###9 重定向
    use Illuminate\Support\Facades\Redirect;

    return redirect('user/login');



