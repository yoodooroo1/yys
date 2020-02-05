/*
	积分商城exchange.html， 我的奖礼品prize.html；我的优惠券 myCoupon.html ; 直接付款facetoFace_pay.html  我的收藏collection.html
*/
/*积分商城*/
/*积分不足提示*/
function lackPoint(){
	layer.msg('您的积分不够或礼品剩余数量为0！',{
		shift:2,
		area:['75%','auto'],
		offset:'80%',
		time: 1500
	});
}
/*兑换礼品弹出*/
function answerLay(goodName,goodPoint,pday,pid,purl,ptype,se,f){
	layer.open({
		type:1,
		closeBtn:false,
		title:false,
		area:['75%','auto'],
		offset:'40%',
		content:'<div class="exPop_cons"><h3 class="exTitle normalLine">'+ goodName +'('+ goodPoint +'积分)</h3>'+
		'<p class="goodNameLineH">兑换成功的礼品可在<font class="textColorRed">我的礼品</font>中查看。</p>'+
		'<p class="textColorGrey fontS goodNameLineH">兑换说明：因实物有可能断货与原因，请在兑换礼品成功后'+pday+'日内到门店兑换，过期作废。</p>'+
		'<div class="exBtn_con"><input name="cancel" type="button" value="取消" class="textRInput"/><input name="exchange" type="button" value="兑换" class="redInput leftBtn"/></div>'+
		'</div>',
		success:function (layero,index){
		   	$(".layui-layer-shade").click(function (){
                layer.close(index); 
            }); //关闭
			$("input[name=cancel]").click(function (){
				layer.close(index);
			});
			$("input[name=exchange]").click(function (){
				layer.close(index);
				var localhostPath = getHostPath();
				$.ajax({   
					url:localhostPath+"/index.php?c=Credit&a=addExchange",   
					type:'post',   
					data:'present_id='+pid+'&present_name='+goodName+'&present_url='+purl+'&type='+ptype+'&se='+se+'&f='+f,   
					async : false, //默认为true 异步   
					error:function(XMLHttpRequest, textStatus, errorThrown){   
						//$('#childtype option[value!=""]').remove(); 
						   alert(XMLHttpRequest.status);
								// alert(XMLHttpRequest.readyState);
								// alert(textStatus);
					},   
					success:function(data){
						if (data =='0') {
						   	var texta = '我的礼品中查看，凭二维码到实体店领取';
							var btn_text = '我的礼品';	
							var presentLink = localhostPath+'/index.php?m=Service&c=Prize&a=index&se='+se+'&f='+f;				
							successEx(goodName,texta,presentLink,btn_text); 
						}else{
							alert(data);
						}
						
					}
			 	});
				
			});
		}
	});
}



function getHostPath(){
	var curWwwPath=window.document.location.href;
    //获取主机地址之后的目录如：/Tmall/index.jsp
    var pathName=window.document.location.pathname;
    var pos=curWwwPath.indexOf(pathName);
    //获取主机地址，如： http://localhost:8080
    var localhostPath=curWwwPath.substring(0,pos);

    return localhostPath;
}
/*优惠券兑换弹出*/
function couponLay(goodName,pid,coupons_id,se,f){
	layer.open({
		type:1,
		closeBtn:false,
		title:false,
		area:['75%','auto'],
		offset:'40%',
		content:'<div class="exPop_cons"><h3 class="exTitle normalLine">'+ goodName +'</h3>'+
		'<div class="exBtn_con"><input name="cancel" type="button" value="取消" class="textRInput"/><input name="exchange" type="button" value="兑换" class="redInput leftBtn"/></div>'+
		'</div>',
		success:function (layero,index){
		   	$(".layui-layer-shade").click(function (){
                layer.close(index); 
            }); //关闭
			$("input[name=cancel]").click(function (){
				layer.close(index);
			});
			$("input[name=exchange]").click(function (){
				layer.close(index);

				var localhostPath = getHostPath();
				$.ajax({   
					url:localhostPath+"/index.php?c=Credit&a=addCoupons",   
					type:'post',   
					data:'present_id='+pid+'&coupons_id='+coupons_id+'&se='+se+'&f='+f,   
					async : false, //默认为true 异步   
					error:function(XMLHttpRequest, textStatus, errorThrown){   
						//$('#childtype option[value!=""]').remove(); 
						   alert(XMLHttpRequest.status);
								// alert(XMLHttpRequest.readyState);
								// alert(textStatus);
					},   
					success:function(data){
						if (data =='0') {
						   	var text_b = '我的优惠券中查看';
							var btn_coupon = '我的优惠券';
							var link_coupon = localhostPath+'/index.php?m=Service&c=Coupon&a=mycoupon&se='+se+'&f='+f;					
							successEx(goodName,text_b,link_coupon,btn_coupon); 
						}else{
							alert(data);
						}

						
					}
			 	});
				
			});
		}
	})
}
/*兑换成功*/
function successEx(goodName,text,link,btn_text){
	layer.open({
		type:1,
		closeBtn:false,
		title:'恭喜您',
		area:['75%','auto'],
		offset:'40%',
		content:'<div class="exPop_cons"><p class="goodNameLineH textColorGrey sucP">积分兑换'+goodName+'已成功，请在'+text+'</font></p>'+
		'<div class="exBtn_con"><a href="'+link+'" class="extoDetail redInput">'+btn_text+'</a><input type="button" name="goOn" value="继续兑换" class="greyInput leftBtn"/></div>'+
		'</div>',
		success:function (layero,index){
			$(".layui-layer-shade").click(function (){
                layer.close(index); 
            }); //关闭
			$("input[name=goOn]").click(function (){
				layer.close(index);
				window.location.replace(window.document.location.href);
			});
		}
	});
}

/*我的奖礼品*/
/*删除弹窗*/
function layerDelete($thisList,eid,name,se,f){
	layer.open({
		type:1,
		area:['75%','auto'],
		offset:'40%',
		closeBtn:false,
		title:false,
		content:'<div class="prizeLayer"><p class="textColorFB goodNameLineH prizeLayer">是否删除('+name+')礼品记录？</p>'+
		'<div class="exBtn_con"><input type="button" name="prizeCancel" value="取消" class=" greyInput"/><input type="button" name="prizeYes" value="确定" class="redInput leftBtn"/></div>'+
		'</div>',
		success:function (layero,index){
			$(".layui-layer-shade").click(function (){
                layer.close(index); 
            }); //关闭
            $("input[name=prizeCancel]").click(function (){
				layer.close(index);
			});
			$("input[name=prizeYes]").click(function (){
				layer.close(index);
				var localhostPath = getHostPath();
				$.ajax({   
					url:localhostPath+"/index.php?c=Prize&a=delPrize",   
					type:'post',   
					data:'exchange_id='+eid+'&se='+se+'&f='+f,   
					async : false, //默认为true 异步   
					error:function(XMLHttpRequest, textStatus, errorThrown){   
						//$('#childtype option[value!=""]').remove(); 
						   alert(XMLHttpRequest.status);
								// alert(XMLHttpRequest.readyState);
								// alert(textStatus);
					},   
					success:function(data){
						if (data =='0') {
						   	$thisList.remove();  //删除
							setTimeout(success(),1000);  //提示成功 
						}else{
							alert(data);
						}
					}
			 	});

			});
		}
	});
}
/*删除成功*/
function success(){
	layer.msg('删除成功',{
		shift:2,
		area:['45%','auto'],
		offset:'80%',
		time:1500
	});
}

/*我的优惠券*/
function deleComp($coupList,coupon_id,se,f){
	layer.open({
		type:1,
		closeBtn:false,
		title:'是否删除优惠券',
		area:['75%','auto'],
		offset:'40%',
		content:'<div class="prizeLayer"><p class="textColorFB goodNameLineH">温馨提示：删除此优惠券将无法再次恢复</p>'+
		'<p class="exBtn_con"><input type="button" name="coupCancle" value="取消" class=" greyInput"/><input type="button" name="couYes" value="确定" class="redInput leftBtn"/></p>'+
		'</div>',
		success:function (layero,index){
			$(".layui-layer-shade").click(function (){
                layer.close(index); 
            });
			$("input[name=coupCancle]").click(function (){
				layer.close(index);
			});
			$("input[name=couYes]").click(function (){
				layer.close(index);
				//$coupList.remove();
				//setTimeout(success(),1000);  //提示成功
					$.ajax({   
				 url:"http://"+window.location.host+"/index.php?c=Coupon&a=delcoupon",   
				 type:'post',   
				 data:'id='+coupon_id+'&se='+se+'&f='+f,   
				 async : false, //默认为true 异步   
				 error:function(XMLHttpRequest, textStatus, errorThrown){   
					//$('#childtype option[value!=""]').remove(); 
					   alert(XMLHttpRequest.status);
							// alert(XMLHttpRequest.readyState);
							// alert(textStatus);
				 },   
				 success:function(data){
					if (data == 0) {
					   layer.close(index);
				     //orderL.remove();
				       //successCancel();
				       //setTimeout(success(),1000);
				       window.location.replace(window.document.location.href);
				       //window.location.replace("http://"+window.location.host+"/index.php?c=Order&a=orderList");
				       return ;
					}else{
						alert(data);
					}
                  
                    
				 }
			   });
			});
		}
	});
}
/*使用说明*/
function instructor(limit_str){
	if(limit_str==''){
		limit_str = '无';
	}
	layer.open({
		type:1,
		title:'使用说明',
		closeBtn:2,
		area:['75%','auto'],
		offset:'40%',
		content:'<div class="prizeLayer"><p class="normalLine">无法使用的商品：抢购促销商品</p>'+
		'<p class="normalLine">无法使用的分类：'+limit_str+'</p>'+
		'</div>',
		success:function(layero,index){
			$(".layui-layer-shade").click(function (){
				layer.close(index);
			});
		}
	});
}

/*直接付款*/
/*输入金额*/
function layerChekFF(){
	layer.msg('输入金额必须大于0',{
		shift:2,
		area:['55%','auto'],
		offset:'80%',
		time:1500
	});
}
/*单选优惠券*/
function layerSingleC(){
	layer.msg('一次只能用一张优惠券,请重新选择',{
		shift:6,
		area:['60%','auto'],
		offset:'80%',
		time:1800
	});
}

/*我的收藏*/

/*确定函数*/
function cancelCollect(box,inpCheck){
	layer.open({
		type:1,
		title:'确定取消收藏？',
		closeBtn:false,
		area:['75%','130px'],
		offset:'40%',
		content:'<div class="exPop_cons">'+
		'<p class="goodNameLineH textColorGrey">取消收藏，该商品将从此列表中删除</p>'+
		'<div class="exBtn_con"><input type="button" value="取消" name="cancel" class="textRInput"/><input type="button" name="doCancel" value="确定" class="redInput leftBtn" /></div>'+
		'</div>',
		success:function (layero,index){
		 	$(".layui-layer-shade").click(function (){
                layer.close(index); 
            });
			$("input[name=cancel]").click(function (){
				layer.close(index);
				inpCheck.attr("checked","checked");
			});
			$("input[name=doCancel]").click(function (){
				url='http://'+window.location.host+'/index.php?m=Service&c=Goods&a=ajax_change_love';
				$.ajax({
					type:'get',
					url:''+url+'',
					data:'goods_id=' + box.attr('data-val') + '&is_love=false' + '&se=' + _STORE_ID_ ,
					dataType:'text',
					success:function(msg){
						layer.close(index);
						box.remove();
						setTimeout(success(),500);
					},
					error:function(){
						alert('访问失败');
					}
				})

			});
		}
	});
}

/*购物车*/
function layerBuy(){
	layer.msg('最少也要购买一件商品嘛  (ˇˍˇ）！',{
		shift:2,
		area:['65%','auto'],
		offset:'60%',
		time:1500
	});
}
/*购物车头部*/
function checkAddHead(){
	var spanVal = $(".firstAdd span").html();	
	if(spanVal == '' || spanVal == undefined){
		$("#shopMarginHeader").attr("class","");
		$("#shopMarginHeader").addClass("martTop");
	}else{
		$("#shopMarginHeader").attr("class","");
		$("#shopMarginHeader").addClass("cartMart");
	}
}

