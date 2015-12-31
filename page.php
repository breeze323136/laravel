<?php
/*
 *  本文件主要介绍使用Laravel内置的Web页面分页和我们使用的接口开发中的分页使用
 *  Anthor : lianger
 *  Email  : liangfeng@shinc.net
 *  To     : www.sexyphp.com
 */

//1、laravel内置的分页，laravel数据库查询方法 get(),改为paginate(分页数)就行，超级简单吧

return DB::table('pay_list')->where('tel', $tel)->orderBy('create_time','DESC')->get();
//replace
return DB::table('pay_list')->where('tel', $tel)->orderBy('create_time','DESC')->paginate(20);


//在页面的使用
 {{$list->links('admin.pageInfo')}}    //不传参的情况

 {{$list->appends(array('tel'=>$tel))->links('admin.pageInfo')}}   //传参的情况
 {{$list->appends(array('tel'=>$tel,'name'=>$name))->links('admin.pageInfo')}} 


 //more
 @if(isset($user_id))

		{{$list->appends(array('user_id'=>$user_id))->links('admin.pageInfo')}}

 @elseif(isset($tel))

		{{$list->appends(array('tel'=>$tel))->links('admin.pageInfo')}}

 @elseif(isset($jnl_no))

		{{$list->appends(array('jnl_no'=>$jnl_no))->links('admin.pageInfo')}}

 @elseif(isset($start_time))

		{{$list->appends(array('start_time'=>$start_time,'end_time'=>$end_time,'choice' =>$choice))->links('admin.pageInfo')}}

 @else

		{{$list->links('admin.pageInfo')}}

@endif


 //so.....easy



/*
 * 2、我们公司常用的分页，可使用于Web分页，接口分页等。只推荐在接口中的分页,Web使用laravel的即可
 * 我们仅作为一个方法来使用，如果项目涉及分页多你可以放在基类，如果一般，可以独立成类，如果比一般低点，需要用到分页的控制器加上此方法即可。
 * 也是入门级的东东，容易上手
 * 
 * 该方法可以在任何框架或者原生PHP中使用，需把  Input::get('length')等的接受参数信息替换成你的即可。
 * Input::get('length')  意思是   $_GET['length']
 */
use Laravel\Model\ChannelModel;
class Demo{
	public  function anyIndex(){
		$pageinfo  				= $this->pageinfo();   //调用分页方法
		$channelModel       	= new ChannelModel();  
		$channelListDatas   	= $channelModel->getChannelListData($shopId, $pageinfo->offset, $pageinfo->length);   //使用分页offse和length
	}

	private function pageinfo($length=20){
		$pageinfo               = new \stdClass;
		$pageinfo->length       = Input::has('length') ? Input::get('length') : $length;;
		$pageinfo->page         = Input::has('page') ? Input::get('page') : 1;
		$pageinfo->end_id       = Input::has('end_id') ? Input::get('end_id') : 0;
		$pageinfo->offset		= $pageinfo->page<=1 ? 0 : ($pageinfo->page-1) * $pageinfo->length;
		//$page->totalNum       = (int)Product::getInstance()->getPurchaseTotalNum();
		$pageinfo->totalNum     = 0;
		$pageinfo->totalPage    = ceil($pageinfo->totalNum/$pageinfo->length);

		return $pageinfo;
	}
}


class ChannelModel(){

	/*
	 * 我的渠道列表
	 */
	public function getChannelListData( $shopId, $offset, $length){   //分页

		return DB::table('shop_channel')->where('shop_id',$shopId)->orderBy('create_time','desc')->skip($offset)->take($length)->get();  //分页

	}
}




?>
