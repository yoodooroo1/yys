
function getAreaList(objId,parentId,t,id){
	var params = {};
	params.parentId = parentId;
	params.type = t;
	$('#'+objId).empty();
	if(t<1){
		$('#areaId3').empty();
		$('#areaId3').html('<option value="">请选择</option>');
	}
	var html = [];
	$.post('/Wx/Areas/queryByList',params,function(data,textStatus){
		html.push('<option value="">请选择</option>');
		var json = {};
		if(typeof(data )=="object"){
			json = data;
		}else{
			json = eval("("+data+")");
		}
		if(json.status=='1' && json.list.length>0){
			var opts = null;
			for(var i=0;i<json.list.length;i++){
				opts = json.list[i];
				html.push('<option value="'+opts.areaId+'" '+((id==opts.areaId)?'selected':'')+'>'+opts.areaName+'</option>');
			}
		}
		$('#'+objId).html(html.join(''));
	});
}



/* function delAddress(id){
	  
	layer.confirm("您确定要删除该地址吗？",{icon: 3, title:'系统提示'},function(tips){
		var ll = layer.load('数据处理中，请稍候...');
		$.post('/Wx/UserAddress/del',{id:id},function(data,textStatus){
			layer.close(ll);
			layer.close(tips);
			var json = {};
			if(typeof(data )=="object"){
				json = data;
			}else{
				json = eval("("+data+")");
			}
			if(json.status=='1'){
				WST.msg('操作成功!', {icon: 1}, function(){
					location.reload();
				});
			}else{
				WST.msg('操作失败!', {icon: 5});
			}
		});
	});   
} */



function toEditAddress(addressId){
	$("#consignee1").hide();
	$("#consignee2").show();
	changeAddress(addressId);
}
function changeAddress(addressId){
	$("#consigneeId").val(addressId);
	chkStr = $("#chkStr").val();
	flag = $("#flag").val(); 
	str = '';
	if(chkStr != ''){
		str =  "/chkStr/"+chkStr;
	}
	else if(flag !=''){
		str = "/flag/"+flag;
	} 
	if(addressId>=1){  
		window.location.href="/Wx/UserAddress/toEdit/id/"+addressId+str;
		return false;
	}else{  
		window.location.href="/Wx/UserAddress/toEdit"+str;
		return false; 
	}
} 

function saveAddress(){
	var addressId = $("#consigneeId").val();
	var userName = $("#userName").val();
	var areaId1 = $("#areaId1").val();
	var areaId2 = $("#areaId2").val();
	var areaId3 = $("#areaId3").val();
	var address = $("#address").val();
	var userPhone = $("#userPhone").val();
	var userTel = $("#userTel").val();
    var isDefault = $("#isDefault1")[0].checked?1:0;
 
	var params = {};
	params.id = addressId;
	params.userName = jQuery.trim(userName);
	params.areaId1 = areaId1;
	params.areaId2 = areaId2;
	params.areaId3 = areaId3;
	params.address = jQuery.trim(address);
	params.userPhone = jQuery.trim(userPhone);
	params.userTel = jQuery.trim(userTel);
	params.isDefault = isDefault;
	
	chkStr = $("#chkStr").val();
	flag = $("#flag").val();
	str = '';
	if(chkStr != ''){
		str =  "/chkStr/"+chkStr;
	}
	else if(flag !=''){
		str = "/flag/"+flag;
	}
	if(params.userName==""){
		WST.msg("请输入收货人", {icon: 5});
		return ;
	}
	if(!WST.checkMinLength(params.userName,2)){
		WST.msg("收货人姓名长度必须大于1个汉字", {icon: 5});
		return ;
	}
	if(params.areaId2<1){
		WST.msg("请选择市", {icon: 5});
		return ;
	}
	if(params.areaId3<1){
		WST.msg("请选择区县", {icon: 5});
		return ;
	}
	if(params.address==""){
		WST.msg("请输入详细地址", {icon: 5});
		return ;
	}
	if(userPhone=="" && userTel==""){
		WST.msg("请输入手机号码或固定电话", {icon: 5});
		return ;
	}
	if(userPhone!="" && !WST.isPhone(params.userPhone)){
		WST.msg("手机号码格式错误", {icon: 5});
		return ;
	}
	/*
	if(userTel!="" && !WST.isTel(params.userTel)){
		WST.msg("固定电话格式错误", {icon: 5});
		return ;
	}*/

	jQuery.post('/Wx/UserAddress/edit' ,params,function(data,textStatus){
		var json = {};
		if(typeof(data )=="object"){
			json = data;
		}else{
			json = eval("("+data+")");
		}
		if(json.status>0){  
			if(addressId==0){
				WST.msg("收货人信息添加成功", {icon: 5});
				window.location.href = "/Wx/user_address/querybypage/"+str;
			}else{
				WST.msg("收货人信息修改成功", {icon: 5});
				window.location.href = "/Wx/user_address/querybypage/"+str;
			}
		}else{   
			WST.msg("收货人信息添加失败", {icon: 5});
		}
	});  
}
function addHour(hour){
    var d=new Date();
    d.setHours(d.getHours()+hour);
    var m=d.getMonth()+1;
    var year = d.getFullYear();
    var month = (m>=10?m:'0'+m);
    
    var day = (d.getDate()>=10)?d.getDate():"0"+d.getDate();
    var h = (d.getHours()>=10)?d.getHours():"0"+d.getHours();
    var min = (d.getMinutes()>=10)?d.getMinutes():"0"+d.getMinutes();
    return (year+'-'+month+'-'+day+" "+h+":"+min+":00");
  }

function delAddress(addressId){
	layer.confirm('您确定删除该地址吗？',{icon: 3, title:'系统提示'}, function(tips){
		var ll = layer.msg('数据处理中，请稍候...', {icon: 16,shade: [0.5, '#B3B3B3']});
		jQuery.post('/Wx/UserAddress/del' ,{id:addressId},function(rsp) {
			layer.close(ll);
	    	layer.close(tips);
			if(rsp){  
				$("#caddress_"+addressId).remove();
				$("#caddress2_"+addressId).remove();
				
				//$("#consigneeId").val(0);  
				//$("#seladdress_0").click();        
			}else{   
				WST.msg("删除失败", {icon: 5});  
			}    
		});
	});
	
}



function getOrderInfo(orderId){
	window.location = Think.U('Home/orders/getOrderInfo','orderId='+orderId);
}

function getPayUrl(){
	
	var params = {};
	params.orderId = $.trim($("#orderId").val());
	params.payCode = $.trim($("#payCode").val());
	params.needPay = $.trim($("#needPay").val());
	if(params.payCode==""){
		WST.msg('请先选择支付方式', {icon: 5});
		return;
	}
	jQuery.post(Think.U('Home/Payments/get'+params.payCode+"URL") ,params,function(data) {
		var json = WST.toJson(data);
		if(json.status==1){
			if(params.payCode=="weixin"){
				location.href = json.url;
			}else{
				window.open(json.url);
			}
		}else if(json.status==-2){
			var rlist = json.rlist;
			var garr = new Array();
			for(var i=0;i<rlist.length;i++){
				garr.push(rlist[i].goodsName+rlist[i].goodsAttrName);
				rlist[i].goodsAttrName
			}
			WST.msg('订单中商品【'+garr.join("，")+'】库存不足，不能进行支付。', {icon: 5});
			
		}else{
			WST.msg('您的订单已支付!', {icon: 5});
			setTimeout(function(){				
				window.location = Think.U('Home/orders/queryDeliveryByPage');
			},1500);
		}
	});
}

$(function() {
	$('input:radio[name="needreceipt"]').click(function(){
		if($(this).val()==1){
			$("#invoiceClientdiv").show();
		}else{
			$("#invoiceClientdiv").hide();
		}		
	});
	
	$("#wst-order-details").click(function(){
		$("#wst-orders-box"). toggle(100);
	});
	
	
	$(".wst-payCode").click(function(){
		$(".wst-payCode-curr").removeClass().addClass("wst-payCode");
		$(this).removeClass().addClass("wst-payCode-curr");
		$("#payCode").val($(this).attr("data"));
	});
	
	$("#isScorePay").click(function(){
		if($("#isScorePay").prop('checked')){
			var totalMoney = $(this).attr("totalMoney");
			var scoreMoney = $(this).attr("scoreMoney");
			$("#totalMoney_span").html((totalMoney-scoreMoney).toFixed(2));
		}else{
			$("#totalMoney_span").html($(this).attr("totalMoney"));
		}
	});
	
	$('input:radio[name="isself"]').click(function(){
		if($(this).val()==0){//送货上门
			$("#totalMoney_span").html($("#totalMoney").val());
			//$("#pay_hd").attr("disabled",false);
			$("[id^=tst_]").val("-1");
			$("[id^=showwarnmsg_]").show();
			$("[id^=deliveryMoney_span_]").each(function(){
				var dvids = $(this).attr("id").split("deliveryMoney_span_");
				$(this).html($("#deliveryMoney_"+dvids[1]).val());
			});
		}else{//自提
			$("#totalMoney_span").html($("#gtotalMoney").val());
			$("[id^=tst_]").val("1");
			$("[id^=showwarnmsg_]").hide();
			$("[id^=deliveryMoney_span_]").each(function(){
				var dvids = $(this).attr("id").split("deliveryMoney_span_");
				$(this).html("¥0");
			});
		}
	});
	
});



