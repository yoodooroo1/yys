/*订单列表 orderList.html _ 2016-0608 junli*/
/*删除订单*/
function deletOrder(orderL,orderId,store_id,f){
	
	layer.open({
		type:1,
		title:false,
		closeBtn:false,
		area:['75%','auto'],
		offset:'30%',
		content:'<div class="prizeLayer deleLayer"><p class="textColorFB goodNameLineH prizeLayer">确认要删除订单?（删除后将无法回复）</p>'+
		'<div class="exBtn_con"><input type="button" name="deleteCancel" value="取消" class="greyInput" />'+
		'<input type="button" name="yesDelete" value="确定" class="redInput leftBtn" /></div>'+
		'</div>',
		success:function (layero,index){
			$(".layui-layer-shade").click(function (){
				layer.close(index);
			});
			$("input[name=deleteCancel]").click(function (){
				layer.close(index);
			});
			$("input[name=yesDelete]").click(function (){
				

				 $.ajax({   
				 url:"http://"+window.location.host+"/index.php?c=Order&a=deleteorder",   
				 type:'post',   
				 data:'orderId='+orderId+'&se='+store_id+'&f='+f,   
				 async : false, //默认为true 异步   
				 error:function(XMLHttpRequest, textStatus, errorThrown){   
					//$('#childtype option[value!=""]').remove(); 
					   alert(XMLHttpRequest.status);
							// alert(XMLHttpRequest.readyState);
							// alert(textStatus);
				 },   
				 success:function(data){
					if (data == 0) {
					   // layer.close(index);
				     //orderL.remove();
				       successDel();
				       // window.location.replace(window.document.location.href);
				       window.location.replace("http://"+window.location.host+"/index.php?c=Order&a=orderList&se="+store_id+"&f="+f);
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
//deletOrder();
function successDel(){
	layer.msg('删除成功',{
		type:1,
		time:1800
	});
}

/*我要退单*/
function popCancelOrder(orderL,orderId,store_id,f){
	
	layer.open({
		type:1,
		title:false,
		area:['75%','auto'],
		offset:'30%',
		closeBtn:false,
		content:'<div class="prizeLayer cancelOrder"><p class="textColorFB goodNameLineH prizeLayer">确认退单？</p>'+
		'<div class="exBtn_con"><input type="button" name="cancelOrder" value="取消" class="greyInput" /><input type="button" name="yesCancelOrder" value="确定" class="redInput leftBtn"/></div></div>',
		success:function (layero,index){
			$(".layui-layer-shade").click(function (){
				layer.close(index);
			});
			$("input[name=cancelOrder]").click(function (){
				layer.close(index);
			});
			$("input[name=yesCancelOrder]").click(function (){
				layer.close(index);
				// orderL.remove();
				// successCancel();

				$.ajax({   
				 url:"http://"+window.location.host+"/index.php?c=Order&a=cancelorder",   
				 type:'post',   
				 data:'orderId='+orderId+'&se='+store_id+'&f='+f,   
				 async : false, //默认为true 异步   
				 error:function(XMLHttpRequest, textStatus, errorThrown){   
					//$('#childtype option[value!=""]').remove(); 
					   alert(XMLHttpRequest.status);
							// alert(XMLHttpRequest.readyState);
							// alert(textStatus);
				 },   
				 success:function(data){
					if (data == 0) {
					   // layer.close(index);
				     //orderL.remove();
				       successCancel();
				       //window.location.replace(window.document.location.href);
				       window.location.replace("http://"+window.location.host+"/index.php?c=Order&a=orderList&se="+store_id+"&f="+f);
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
//popCancelOrder();
function successCancel(){
	layer.msg('退单成功',{
		type:1,
		time:1800
	});
}
/*再次购买*/
function reBuyLay(orderL,orderId,orderstoreid,f){
	layer.open({
		type:1,
		area:['75%','auto'],
		offset:'30%',
		title:false,
		closeBtn:false,
		content:'<div class="prizeLayer reBuyLayer"><p class="textColorFB goodNameLineH prizeLayer">确定再次购买？</p>'+
		'<div class="exBtn_con"><input type="button" name="cancelRebuy" value="取消" class="greyInput" /><input type="button" name="yesRebuy" value="确定" class="redInput leftBtn" /></div>'+
		'</div>',
		success:function (layero,index){
			$(".layui-layer-shade").click(function (){
				layer.close(index);
			});
			$("input[name=cancelRebuy]").click(function (){
				layer.close(index);
			});
			$("input[name=yesRebuy]").click(function (){
				layer.close(index);
				//orderL.remove();
				// window.location = "{:U('Shop/shop')}";
				 $.ajax({   
				 url:"http://"+window.location.host+"/index.php?c=Order&a=reBuy",   
				 type:'post',   
				 data:'orderId='+orderId+'&se='+orderstoreid+'&f='+f,   
				 async : false, //默认为true 异步   
				 error:function(XMLHttpRequest, textStatus, errorThrown){   
					//$('#childtype option[value!=""]').remove(); 
					   alert(XMLHttpRequest.status);
							// alert(XMLHttpRequest.readyState);
							// alert(textStatus);
				 },   
				 success:function(data){
					if (data == 0) {
					   // layer.close(index);
				     //orderL.remove();
				       // successDel();
				       // window.location.replace(window.document.location.href);
				       // return ;
				       //document.write(data);
				       //location.replace("{:U('Shop/shop')}");

				       window.location.replace("http://"+window.location.host+"/index.php?c=Shop&a=shop&se="+orderstoreid+"&f="+f);

					}else{
						//document.write(data);
						alert(data);
					}
                  
                    
				 }
			 });

			});
		}
	});
}


/*确认收货*/
function receiveOrderLay(orderL,orderId,store_id,f){
	layer.open({
		type:1,
		area:['75%','auto'],
		offset:'30%',
		title:false,
		closeBtn:false,
		content:'<div class="prizeLayer reBuyLayer"><p class="textColorFB goodNameLineH prizeLayer">商品已接收？</p>'+
		'<div class="exBtn_con"><input type="button" name="cancelreceive" value="取消" class="greyInput" /><input type="button" name="yeslreceive" value="确定" class="redInput leftBtn" /></div>'+
		'</div>',
		success:function (layero,index){
			$(".layui-layer-shade").click(function (){
				layer.close(index);
			});
			$("input[name=cancelreceive]").click(function (){
				layer.close(index);
			});
			$("input[name=yeslreceive]").click(function (){
				layer.close(index);
				//orderL.remove();
				// window.location = "{:U('Shop/shop')}";

				 $.ajax({   
				 url:"http://"+window.location.host+"/index.php?c=Order&a=receiveOrder",   
				 type:'post',   
				 data:'orderId='+orderId+'&se='+store_id+'&f='+f,   
				 async : false, //默认为true 异步   
				 error:function(XMLHttpRequest, textStatus, errorThrown){   
					//$('#childtype option[value!=""]').remove(); 
					   alert(XMLHttpRequest.status);
							// alert(XMLHttpRequest.readyState);
							// alert(textStatus);
				 },   
				 success:function(data){
					if (data == 0) {
					   // layer.close(index);
				     //orderL.remove();
				       // successDel();
				       // window.location.replace(window.document.location.href);
				       // return ;
				       //document.write(data);
				       //location.replace("{:U('Shop/shop')}");

				        receivesuccess();
				      // window.location.replace(window.document.location.href);
                      window.location.replace("http://"+window.location.host+"/index.php?c=Order&a=orderList&se="+store_id+"&f="+f);
					}else{
						//document.write(data);
						alert(data);
					}
                  
                    
				 }
			 });

			});
		}
	});
}


/*退款*/
function tuikuanOrderLay(orderL,orderId,store_id,f){
	layer.open({
		type:1,
		area:['75%','auto'],
		offset:'30%',
		title:false,
		closeBtn:false,
		content:'<div class="prizeLayer reBuyLayer"><p class="textColorFB goodNameLineH prizeLayer">确认退款？</p>'+
		'<div class="exBtn_con"><input type="button" name="cancelreceive" value="取消" class="greyInput" /><input type="button" name="yeslreceive" value="确定" class="redInput leftBtn" /></div>'+
		'</div>',
		success:function (layero,index){
			$(".layui-layer-shade").click(function (){
				layer.close(index);
			});
			$("input[name=cancelreceive]").click(function (){
				layer.close(index);
			});
			$("input[name=yeslreceive]").click(function (){
				layer.close(index);
				//orderL.remove();
				// window.location = "{:U('Shop/shop')}";

				 $.ajax({   
				 url:"http://"+window.location.host+"/index.php?c=Order&a=tuikuan",   
				 type:'post',   
				 data:'orderId='+orderId+'&se='+store_id+'&f='+f,   
				 async : false, //默认为true 异步   
				 error:function(XMLHttpRequest, textStatus, errorThrown){   
					//$('#childtype option[value!=""]').remove(); 
					   alert(XMLHttpRequest.status);
							// alert(XMLHttpRequest.readyState);
							// alert(textStatus);
				 },   
				 success:function(data){
					if (data == 0) {
					   // layer.close(index);
				       //orderL.remove();
				       // successDel();
				       // window.location.replace(window.document.location.href);
				       // return ;
				       //document.write(data);
				       //location.replace("{:U('Shop/shop')}");

				        receivesuccess();
				      // window.location.replace(window.document.location.href);
                      window.location.replace("http://"+window.location.host+"/index.php?c=Order&a=orderList&se="+store_id+"&f="+f);
					}else{
						//document.write(data);
						alert(data);
					}
                  
                    
				 }
			 });

			});
		}
	});
}

function receivesuccess(){
	layer.msg('确认收货成功',{
		type:1,
		time:1800
	});
}