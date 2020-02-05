wx.config({        
    debug:false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
    appId: appid, // 必填，公众号的唯一标识
    timestamp: mt, // 必填，生成签名的时间戳
    nonceStr: 'a955202e5278b1a66016a04fd417dd22', // 必填，生成签名的随机串
    signature: signature,// 必填，签名，见附录1
    jsApiList: ['onMenuShareAppMessage','onMenuShareTimeline'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
});        
wx.ready(function () {
    if(main_store==1){
      var sharurl ='http://m.qgja.com/index.php?c=MallHome&a=index&f='+from_user+'&se='+store_id;
    }else{
      var sharurl ='http://m.qgja.com/index.php?c=index&f='+from_user+'&se='+store_id;
    }
	wx.onMenuShareAppMessage({       //分享给朋友
		title: store_name, // 分享标题
		desc: '开启极速开店，购物，聊天之旅', // 分享描述  
		link: sharurl, // 分享链接
		imgUrl: store_label, // 分享图标  
		success: function () {   
			   
            
            url='http://'+window.location.host+'/index.php?m=Service&c=Share&a=index';
            $.ajax({
                type:'get',
                url:''+url+'',
                data:'share_member_id=' + from_user +'&share_store_id='+store_id +'&sharetype=1&f='+from_user+'&se='+store_id,
                dataType:'text',
                success:function(msg){
                  
                },
                error:function(){
                   
                }
            })
          alert('分享成功');
		}, 
	});   
	wx.onMenuShareTimeline({       ///分享到朋友圈  
    title: store_name, // 分享标题  
    link: sharurl, // 分享链接
    imgUrl: store_label, // 分享图标
    success: function () { 
        alert('分享成功');  
         url='http://'+window.location.host+'/index.php?m=Service&c=Share&a=index';
            $.ajax({
                type:'get',
                url:''+url+'',
                data:'share_member_id=' + from_user +'&share_store_id='+store_id +'&sharetype=2&f='+from_user+'&se='+store_id,
                dataType:'text',
                success:function(msg){
                  
                },
                error:function(){
                   
                }
            })    
      }
    });  
});       
  