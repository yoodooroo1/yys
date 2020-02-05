/*分类页*/
/*---------右侧div 高度----------*/
function contentH(){
	var windH  = $(window).height();			
	var cententH = windH - 130;  //130为头部和底部的高			
	$(".classifyRight").height(cententH);   //右
	$(".Huifold").height(cententH);  //左
}

/*----------------滚动-----------------*/
function classRight(){
	var scrollH = $(".classifyRight").get(0).scrollHeight;			
	var divSroTop =$('.classifyRight').scrollTop();			
	var viewH = $(".classifyRight").get(0).offsetHeight;
	//console.log("滚动高度：" + scrollH + "--div的视高：" + viewH + "--顶部：" + divSroTop)	

	if (divSroTop/(scrollH - viewH) >= 0.90){  
		//到达底部10%，加载新内容
		//console.log("到底部了！");
		 onlyOne(); 			
	}
	/*回到顶部*/
	if(divSroTop >= 300){
		 $("#goToTop").fadeIn(500); 
	}else{
		$("#goToTop").fadeOut();
	}
	
}
$("#goToTop").click(function(){  
		$(".classifyRight").animate({scrollTop:0},500);  
	   // return false;    //不知道什么作用
	});
function onlyOne() {
    if(flag) {
        clickMe();
    }
    flag = 0;
}

/*更多内容ajax*/
function clickMe(){
		url='http://'+window.location.host+'/index.php?m=Service&c=Classify&a=ajax_get_classify_next_page';
		$.ajax({
			type:'get',
			url:''+url+'',
			data:'page=' + Page + '&gc_id=' + now_gc_id + '&sort_type=' + _SORT_TYPE_ + '&se=' + _STORE_ID_ ,
			dataType:'json',

			success:function(msg){
				if(msg.list==''){
					var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
					$(".classifyRight").append(endBar);
					setTimeout(function (){
						$("#endBar").slideUp();
					},1600);
					// alert('没有更多了');
				}else{
					Page = msg.page;
					var html = template('test',msg);
					$(".classifyRight").append(html);				
					flag = 1;					
        			imgHeight();
				}				
			},
			error:function(){
				alert('访问失败');
			}
		})
	
}
/*橱窗式图片，高度限制*/
function imgHeight(){
	$(".windList>img,.goodsList>img").each(function (){
		$this = $(this);
		var imgW = $this.width();	
		$this.height(imgW);
	});	
}
/*---------找好店------*/
function onlyOneMallList() {
    if(flag) {
        clickMeMallList();

    }
    flag = 0;
}

/*更多内容ajax-找好店*/
function clickMeMallList(){
	if (_OPEN_FROM_ == 'rush_goods') {
		url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=ajax_rush_next_page';
		$.ajax({
			type:'get',
			url:''+url+'',
			data:'page=' + Page + '&se=' + _STORE_ID_ + '&in_stock=' + _IN_STOCK_ + '&sort_type=' + _SORT_TYPE_ ,
			dataType:'json',

			success:function(msg){
				if (Page == 1) {
					$(".classifyRight").empty();
				}
				if(msg.list==''){
					var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
					$(".classifyRight").append(endBar);
					setTimeout(function (){
						$("#endBar").slideUp();
					},1600);
					// alert('没有更多了');
				}else{
					Page = msg.page;
					var html = template('test',msg);
					$(".classifyRight").append(html);				
					flag = 1;
				}				
			},
			error:function(){
				alert('访问失败');
			}
		})
	} else if (_OPEN_FROM_ == 'sales_goods') {
		url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=ajax_sales_next_page';
		$.ajax({
			type:'get',
			url:''+url+'',
			data:'page=' + Page + '&se=' + _STORE_ID_ + '&in_stock=' + _IN_STOCK_ + '&sort_type=' + _SORT_TYPE_ ,
			dataType:'json',

			success:function(msg){
				if (Page == 1) {
					$(".classifyRight").empty();
				}
				if(msg.list==''){
					var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
					$(".classifyRight").append(endBar);
					setTimeout(function (){
						$("#endBar").slideUp();
					},1600);
					// alert('没有更多了');
				}else{
					Page = msg.page;
					var html = template('test',msg);
					$(".classifyRight").append(html);				
					flag = 1;
				}				
			},
			error:function(){
				alert('访问失败');
			}
		})
	} else if (_OPEN_FROM_ == 'hot_goods') {
		url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=ajax_hot_next_page';
		$.ajax({
			type:'get',
			url:''+url+'',
			data:'page=' + Page + '&se=' + _STORE_ID_ + '&in_stock=' + _IN_STOCK_ + '&sort_type=' + _SORT_TYPE_ ,
			dataType:'json',

			success:function(msg){
				if (Page == 1) {
					$(".classifyRight").empty();
				}
				if(msg.list==''){
					var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
					$(".classifyRight").append(endBar);
					setTimeout(function (){
						$("#endBar").slideUp();
					},1600);
					// alert('没有更多了');
				}else{
					Page = msg.page;
					var html = template('test',msg);
					$(".classifyRight").append(html);				
					flag = 1;
				}				
			},
			error:function(){
				alert('访问失败');
			}
		})
	} else if (_OPEN_FROM_ == 'choice_goods') {
		url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=ajax_choice_next_page';
		$.ajax({
			type:'get',
			url:''+url+'',
			data:'page=' + Page + '&se=' + _STORE_ID_ + '&in_stock=' + _IN_STOCK_ + '&sort_type=' + _SORT_TYPE_ ,
			dataType:'json',

			success:function(msg){
				if (Page == 1) {
					$(".classifyRight").empty();
				}
				if(msg.list==''){
					var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
					$(".classifyRight").append(endBar);
					setTimeout(function (){
						$("#endBar").slideUp();
					},1600);
					// alert('没有更多了');
				}else{
					Page = msg.page;
					var html = template('test',msg);
					$(".classifyRight").append(html);				
					flag = 1;
				}				
			},
			error:function(){
				alert('访问失败');
			}
		})
	} else if (_OPEN_FROM_ == '1' || _OPEN_FROM_ == '2' || _OPEN_FROM_ == '4' || _OPEN_FROM_ == '5' || _OPEN_FROM_ == '6' || _OPEN_FROM_ == '7' || _OPEN_FROM_ == '8') {
		url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=ajax_mall_goods_next_page';
		$.ajax({
			type:'get',
			url:''+url+'',
			data:'page=' + Page + '&type=' + _OPEN_FROM_ + '&se=' + _STORE_ID_ + '&in_stock=' + _IN_STOCK_ + '&sort_type=' + _SORT_TYPE_ ,
			dataType:'json',

			success:function(msg){
				if (Page == 1) {
					$(".classifyRight").empty();
				}
				if(msg.list==''){
					var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
					$(".classifyRight").append(endBar);
					setTimeout(function (){
						$("#endBar").slideUp();
					},1600);
					// alert('没有更多了');
				}else{
					Page = msg.page;
					var html = template('test',msg);
					$(".classifyRight").append(html);				
					flag = 1;
				}				
			},
			error:function(){
				alert('访问失败');
			}
		})
	} else if (_OPEN_FROM_ == '3') {
		url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=ajax_mall_goods_next_page';
		$.ajax({
			type:'get',
			url:''+url+'',
			data:'page=' + Page + '&type=' + _OPEN_FROM_ + '&se=' + _STORE_ID_ ,
			dataType:'json',

			success:function(msg){
				if (Page == 1) {
					$(".mallShopList").empty();
				}
				if(msg.list==''){
					var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
					$(".mallShopList").append(endBar);
					setTimeout(function (){
						$("#endBar").slideUp();
					},1600);
					// alert('没有更多了');
				}else{
					Page = msg.page;
					var html = template('test',msg);
					$(".mallShopList").append(html);				
					flag = 1;
				}				
			},
			error:function(){
				alert('访问失败');
			}
		})
	} else if (_OPEN_FROM_ == '9') {
		url='http://'+window.location.host+'/index.php?m=Service&c=MallStore&a=ajax_oftenstore_next_page';
		$.ajax({
			type:'get',
			url:''+url+'',
			data:'page=' + Page + '&type=' + _OPEN_FROM_ + '&se=' + _STORE_ID_ ,
			dataType:'json',

			success:function(msg){
				if (Page == 1) {
					$(".mallShopList").empty();
				}
				if(msg.list==''){
					var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
					$(".mallShopList").append(endBar);
					setTimeout(function (){
						$("#endBar").slideUp();
					},1600);
					// alert('没有更多了');
				}else{
					Page = msg.page;
					var html = template('test',msg);
					$(".mallShopList").append(html);				
					flag = 1;
				}				
				//document.write(msg);
			},
			error:function(){
				alert('访问失败');
			}
		})
	} else if (_OPEN_FROM_ == 'zhuanqu') {
		url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=ajax_zhuanqu_next_page';
		$.ajax({
			type:'get',
			url:''+url+'',
			data:'page=' + Page + '&se=' + _STORE_ID_ + '&gc_id=' + now_gc_id + '&search=' + _SEARCH_ + '&in_stock=' + _IN_STOCK_ + '&sort_type=' + _SORT_TYPE_ ,
			dataType:'json',

			success:function(msg){
				if (Page == 1) {
					$(".classifyRight").empty();
				}
				if(msg.list==''){
					var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
					$(".classifyRight").append(endBar);
					setTimeout(function (){
						$("#endBar").slideUp();
					},1600);
					// alert('没有更多了');
				}else{
					Page = msg.page;
					var html = template('test',msg);
					$(".classifyRight").append(html);				
					flag = 1;
				}				
			},
			error:function(){
				alert('访问失败');
			}
		})
	}
}

/*--------------搜索结果输出-------------*/
function searchOne(content,searchVal) {
    if(flag) {
       search(content,searchVal)
    }
    flag = 0;
}
function search(content,searchVal){
	url='http://'+window.location.host+'/index.php?m=Service&c=Classify&a=ajax_get_search_next_page';
	$.ajax({
		type:'get',
		url:''+url+'',
		data:'page=' + Page + '&search=' + searchVal + '&se=' + _STORE_ID_ ,
		dataType:'json',
		success:function(msg){			
			if(msg.list==''){				
				/*var endBar = '<div class="bgWhite fontSize_85 normalLine textAlign_center" id="endBar">没有更多了</div>'
				 $("#searchResult").append(endBar);
				 setTimeout(function (){
				 	$("#endBar").slideUp();
				 },1600);*/	
				 flag = 1;			
			}else{
				Page = msg.page;
				var html = template('test',msg);
				content.append(html);			
				flag = 1;
				imgHeight();
			}				
		},
		error:function(){
			alert('访问失败1111');
		}
	})
	// var data = {"page":"1","list":[{"goods_id":"208196","store_id":"292","goods_name":"顽皮兔 乳酸优C果冻 360g","goods_price":"4.00","goods_desc":"顽皮兔 乳酸优C果冻 360g","main_img":"http:\/\/dl.devp.com.cn\/data\/upload\/05203550862846293.jpg","is_hot":1,"spec":[{"spec_id":0,"name":"a1","price":4,"storage":-1,"pv":1,"new_price":"2.00","buy_num":0},{"spec_id":1,"name":"a2","price":10,"storage":-1,"pv":2.5,"new_price":-1,"buy_num":0}],"new_price":"2.00~10.00","state":2},{"goods_id":"208194","store_id":"292","goods_name":"茶花 吸盘挂巾架","goods_price":"50.00","goods_desc":"茶花 吸盘挂巾架","main_img":"http:\/\/dl.devp.com.cn\/data\/upload\/05140286822439042.jpg","is_hot":0,"spec":[{"spec_id":0,"name":"b2","price":50,"storage":-1,"pv":12.5,"new_price":-1,"buy_num":0}],"buy_num":0,"new_price":"-1","state":0}]};	
}

/*----------------添加商品加减-------------------*/

/*加入购物车按钮变化*/
function addChange(index){
	var newBtn = $("#reBtn_" + index);  //加减按钮

	url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=add_cart';
	$.ajax({
		type:'get',
		url:''+url+'',
		data:'gs_id=' + index + '&se=' + _STORE_ID_ ,  //goods_id
		dataType:'text',
		success:function(msg){
			$("#amountLabel").show();  //购物车数量label显示
			$("#firstad_"+index).hide();
			newBtn.show();
			newBtn.find("input[type=tel]").val("1");	
			newBtn.find("input[type=hidden]").val("1");	 //隐藏值
		},
		error:function(){
			alert('访问失败');
		}
	})
}
/*一级加号+动作效果*/
function firstAdd(event,$this,index){
	var offset = $("#end").offset();
	var divScroTop = $(document).scrollTop();	
	var addcar = $this;
	var img = $("#goods_"+ index).attr('src');
	var flyer = $('<img class="u-flyer" src="'+img+'">');
	var singlePrice = Number($("#sprice_" + index).text());  //获得单价
	//console.log("单价：" + singlePrice);
	flyer.fly({
		start: {
			left: event.pageX,
			top: event.pageY - divScroTop
		},
		end: {
			left: offset.left+10,
			top: offset.top - divScroTop,
			width: 0,
			height: 0
		},
		onEnd: function(){
			$("#msg").show().animate({width: '250px'}, 200).fadeOut(1000);  //提示框
			//addcar.css("cursor","default").removeClass('orange').unbind('click');   //加入购物车按钮变化
			this.destory();				
			addamount(); /*购物车数量加*/
			getAddTal(singlePrice); /*总价加*/
		}
	});
}
/*二级加号+动效*/
function reAddShow(event,gid){
	var offset = $("#end").offset();
	var divScroTop = $(document).scrollTop();	
	var img = $("#goods_"+ gid).attr('src');
	var flyer = $('<img class="u-flyer" src="'+img+'">');
	var singlePrice = Number($("#sprice_" + gid).text());  //获得单价
	flyer.fly({
		start: {
			left: event.pageX - 30,
			//top: event.pageY - divScroTop
			top: event.pageY - 20
		},
		end: {
			left: offset.left+10,
			top: offset.top - divScroTop,
			width: 0,
			height: 0
		},
		onEnd: function(){
			//$("#msg").show().animate({width: '250px'}, 200).fadeOut(1000);  //提示框
			//addcar.css("cursor","default").removeClass('orange').unbind('click');   //加入购物车按钮变化，但此加入
			this.destory();				
			addamount();/*购物车数量加*/
			getAddTal(singlePrice); /*总价加*/
		}
	});
}

/*二级加*/
function reAdd(gid){
	$this = $(this);
	url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=add_cart';
	$.ajax({
		type:'get',
		url:''+url+'',
		data:'gs_id=' + gid + '&se=' + _STORE_ID_ ,
		dataType:'text',
		success:function(msg){
			var getInput = $("#goodA_" + gid);   //input 对象
			var inputNVal = Number(getInput.val());  //当前值
			//console.log("当前input框内数量：" + inputNVal);
			var newVal = inputNVal + 1;
			//console.log("新的值：" + newVal);

			getInput.val(newVal);   //赋新值
			$("#oldA_"+ gid).val(newVal); //隐藏值
		},
		error:function(){
			alert('访问失败');
		}
	})
}
/*减*/
function removeA(gid){
	url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=sub_cart';
	$.ajax({
		type:'get',
		url:''+url+'',
		data:'gs_id=' + gid + '&se=' + _STORE_ID_ ,
		dataType:'text',
		success:function(msg){
			var getInput = $("#goodA_" + gid);   //input 对象
			var inputNVal = Number(getInput.val());  //当前值
			//console.log("当前input框内数量：" + inputNVal);
			var newVal = inputNVal - 1;
			//console.log("新的值：" + newVal);

			getInput.val(newVal); 
			$("#oldA_"+ gid).val(newVal); //隐藏值  
			if(newVal <= 0){
				$("#reBtn_" + gid).hide();
				$("#firstad_" + gid).show();
			}else{
				
			}
		},
		error:function(){
			alert('访问失败');
		}
	})
}

/*减-特效*/
function reMoveShow(event,gid){
	var offset = $("#end").offset();
	var divScroTop = $(document).scrollTop();	
	var windowHeight = $(window).height();  //计算窗口高度	
	
	var singlePrice = Number($("#sprice_" + gid).text());	//获得单价		
	var flyer = $('<span class="theRedCircle"></span>');
	flyer.fly({
		start: {				
			left: offset.left + 30,  //加--向x轴正方向
			top: offset.top - divScroTop //加--向y轴负方向
		},
		end: {
			left: '0',
			top: windowHeight,
			width: 0,
			height: 0
		},
		onEnd: function(){
			//$("#msg").show().animate({width: '250px'}, 200).fadeOut(1000);  //提示框				
			this.destory();
			subAmount(); /*购物车总计*/		
			getSubTotal(singlePrice); /*总价减*/	
		}
	});
}

/*------------输入-------*/
function inputkey(event,gid){	
	var val = $("#goodA_"+gid).val();  //一进入的值
	var key = event.which; //e.which是按键的值---enter

	$("#goodA_"+gid).css("border","1px solid #D83838");
	if (key == 13) {
		$("#goodA_"+gid).blur();			
	}
}
/*失去焦点-----//不计算总价-*/
function inputBlur(gid){	
	var thidVal = $("#goodA_"+gid).val();   //失去焦点时的数量
	var thisHidden = $("#oldA_"+gid);
	var thsParent = $("reBtn_" + gid); 
	var thBroth = $("firstad_" + gid);
	var newAb = Number($("#goodA_"+gid).val());  //
	// alert(gid + '  ' + newAb);
	url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=edit_cart';
	$.ajax({
		type:'get',
		url:''+url+'',
		data:'gs_id=' + gid + '&se=' + _STORE_ID_ + '&gou_num=' + newAb ,
		dataType:'text',
		success:function(msg){
			// alert(msg);
			var oldgoodS = Number(thisHidden.val());  //获取历史数量
	
			//console.log("历史数量:" + oldgoodS );
			//console.log("当前的值：" + thidVal);
	
			$("#goodA_"+gid).css("border","1px solid #DFDFDF");
			if(thidVal == 0 || thidVal == ''){
				thsParent.hide();
				thBroth.show();
			}
			thisHidden.val(thidVal);//重置新的单品总历史数量
			classPutTotal(oldgoodS,newAb); //总价，总数
		},
		error:function(){
			alert('访问失败');
		}
	})
}

/*失去焦点------计算总价*/
function blurTotal(gid){	
	var thidVal = $("#goodA_"+gid).val();
	var thisHidden = $("#oldA_"+gid);
	var thsParent = $("#reBtn_" + gid); 
	var thBroth = $("#firstad_" + gid);
	var newAb = Number($("#goodA_"+gid).val());    //新的数量
	// alert(gid + ' aaa  ' + newAb + ' se= ' + _STORE_ID_);
	url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=edit_cart';
	$.ajax({
		type:'get',
		url:''+url+'',
		data:'gs_id=' + gid + '&se=' + _STORE_ID_ + '&gou_num=' + newAb ,
		dataType:'text',
		success:function(msg){
			// alert('aaa  ' + msg);
			var oldgoodS = Number(thisHidden.val());  //获取历史数量
			var singlePrice = Number($("#sprice_" + gid).text()); //对应单价
			//console.log("历史数量:" + oldgoodS +"-对应单价:" + singlePrice);
			//console.log("当前的值：" + thidVal);
			
			$("#goodA_"+gid).css("border","1px solid #efefef");
			if(thidVal == 0 || thidVal == ''){
				thsParent.hide();
				thBroth.show();
			}
			thisHidden.val(thidVal);//重置新的单品总历史数量
			putTotal(oldgoodS,singlePrice,newAb); //总价，总数
		},
		error:function(){
			alert('访问失败');
		}
	})
}

