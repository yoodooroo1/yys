/*首页*/

/*Vip 进度条*/
$(function (){
	var val = Number($("#first").html());
	var  VipMax = Number($("#max").html());
	//console.log("value：" + val + "-最大值：" + VipMax);
		$("#pbBar").progressbar({
		max: VipMax,
		value:val
	});
});
/*首页商家联盟 最后一个border*/
$(function (){
		$(".leagueShop:last").css("border","0");
		//$(".leagueShop:last").css("margin-bottom","175px");
});
/*首页 回到顶部*/
$(function(){  
        //当滚动条的位置处于距顶部100像素以下时，跳转链接出现，否则消失  
        $(function () {  
            $(window).scroll(function(){  
                if ($(window).scrollTop()>500){  
                    $("#goToTop").fadeIn(500);  
                }  
                else  
                {  
                    $("#goToTop").fadeOut(500);  
                }  
            });  
  
            //当点击跳转链接后，回到页面顶部位置  
  
            $("#goToTop").click(function(){  
                $('body,html').animate({scrollTop:0},500);  
               // return false;    //不知道什么作用
            });  
        });  
    });
/*vip 登级说明*/
// function VipLayer(vipdesc){
//     var mcontent = '<div class="vipIndex"><p class="textColorGrey fontS goodNameLineH">'+vipdesc+'</p>'+
//         '<p class="normalLine textColorRed fontS"><font class="iconFont">VIP2</font>&nbsp;全店购物享9.9折</p>'+
//         '<p class="normalLine textColorRed fontS"><font class="iconFont ">VIP3</font>&nbsp;全店购物享9.8折</p>'+
//         '<p class="normalLine textColorRed fontS"><font class="iconFont">VIP4</font>&nbsp;全店购物享9.7折</p>'+
//         '<p class="btnCenter"><button id="closeBtn" class="textRInput triBtn">知道了</button></p>'+
//         '</div>';
//             // $leveldesc = '<div class="vipIndex"><p class="textColorGrey fontS goodNameLineH">'.$vipdesc.'</p>';
//             // for ($i=0; $i < count($storevip); $i++) { 
//             //     $leveldesc =$leveldesc.'<p class="normalLine textColorRed fontS"><font class="iconFont">VIP'.$storevip[$i]["vip_level"].
//             //                 '</font>&nbsp;全店购物享'.$storevip[$i]["discount"].'折</p>'; 
//             // }
//             // $leveldesc =$leveldesc.'<p class="btnCenter"><button id="closeBtn" class="textRInput triBtn">知道了</button></p></div>';

//     layer.open({
//         type: 1,
//         title: false,
//         area: '75%',
//         closeBtn: 0,
//         content:mcontent,
//         success:function (layero,index){
//             $("#closeBtn").click(function (){
//                 layer.close(index);
//             });
//             $(".layui-layer-shade").click(function (){
//                 layer.close(index); 
//             }); //关闭
//         }
//     });
// }
/*限时秒杀_商品 弹出*/
function popMsg(){
    layer.msg('抢购活动暂未开始，敬请期待',{
        area: ['75%', 'auto'],
        shift: 2,
        time: 1500,
        offset: '60%'
    });
}


/*签到成功*/
$(function (){
    var windowHeight = $(document).height() + 'px';
    //console.log(windowHeight);        
    $("#successLayer>img").css("height",windowHeight);
});

/*公告轮播*/
var swiper_bulletin = new Swiper('#bulletinShow', {
    paginationClickable: true,
    direction:'vertical',
    autoplayDisableOnInteraction:false,
    speed:350,
    autoplay:1800,
    loop:true
});
swiper_bulletin.detachEvents();  //禁止手动滑动

/*橱窗式，高度限制*/
function imgHeight(){
	$(".windList>img").each(function (){
		var imgW = $(this).width();
		$(".windList>img").height(imgW + 'px');
	});	
}