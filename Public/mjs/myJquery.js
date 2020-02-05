/*2016-05-16
	author:junli
	厦门微距点信息科技有限公司
*/
/*订单大于100 显示‘...’*/
function showOrHide(){
	var cartAmount = Number($("#amountLabel").text());
	//console.log("购物车数量：" + cartAmount);
	if(cartAmount <= 0){
		$("#amountLabel").hide();
	}else {
		$("#amountLabel").show();
		if(cartAmount >= 100){
			$("#amountLabel").show();
			$("#amountLabel").text("...");
		}
	} 
}
/*进入某页订单数*/
function alreadyVal(){
	var hiddenVal = $("#alreadyVal").val();
	//console.log("进入该页前订单数" + hiddenVal);  //进入该页前订单数
	if(hiddenVal <= 0){
		$("#amountLabel").hide();
	}else if(hiddenVal >= 100){
		$("#amountLabel").text("...");
	}
}

/*购物车数量加*/
function addamount(){      
	//var cartAmount = Number($("#amountLabel").text());  //当前值
	var cartAmount = Number($("#alreadyVal").val());  //当前值
	//console.log("购物车数量：" + cartAmount);
	var newValue = cartAmount + 1;
	$("#amountLabel").text(newValue);
	$("#amount_2_label").text(newValue);  //购物车统计
	$("#alreadyVal").val(newValue);  //历史数据
	showOrHide();  //大于99显示'...'

	/*购物车优惠券显示控制*/
	$(".fflist_coup input[type=checkbox]").removeAttr("checked");
	$(".fflist_coup>label").removeClass("activeLabel");
	/*if(newValue == 0 || newValue == ''||coupons.length==0){*/
	if(newValue == 0 || newValue == ''){	
		$("#ffCoup span").html('暂无可用优惠券');
	}else{
		$("#ffCoup span").html('有可用优惠券');
	}	
}
/*加--总价变化 circle*/
function getAddTal(single){	
	var oldTotal = Number($("#alreadyTotal").val());
		/*console.log("单价：" + single);
		console.log(" oldTotal:"+ oldTotal);*/
	var newTotal = (oldTotal + single).toFixed(2);
		/*console.log("新的总价：" + newTotal);*/
	$("#totalFooter").text(newTotal);  //写入新总价	
	$("input[name=alreadyTotal]").val(newTotal); //历史总价
	changeBtn(newTotal);  //购物车页面——结算按钮
}
/*购物车数量减*/
function subAmount(){
	var cartAmount = Number($("#alreadyVal").val());  //当前值
	//console.log("购物车数量：" + cartAmount);
	var newValue = cartAmount - 1;
	$("#amountLabel").text(newValue);  //写入数量
	$("#amount_2_label").text(newValue);  //购物车统计
	$("#alreadyVal").val(newValue);  //历史数据
	showOrHide();  //小于0 不显示；

	/*购物车优惠券显示控制*/
	$(".fflist_coup input[type=checkbox]").removeAttr("checked");
	$(".fflist_coup>label").removeClass("activeLabel");
	if(newValue == 0 || newValue == ''||coupons.length==0){
		$("#ffCoup span").html('暂无可用优惠券');
	}else{
		$("#ffCoup span").html('有可用优惠券');
	}
}
/*减--总价变化 circle*/
function getSubTotal(single){
	var oldTotal = Number($("#alreadyTotal").val());
	//console.log("单价："+ single);
	var newTotal = (oldTotal - single).toFixed(2);
	$("#totalFooter").text(newTotal);  //写入新总价
	$("input[name=alreadyTotal]").val(newTotal); //历史总价 
	changeBtn(newTotal);  //购物车页面——结算按钮 
}
/*失去焦点后计算总价 --商品详情-输入,按钮/抢购-输入,购物车算总价*/
function putTotal(oldgoodS,singlePrice,newAb){	
	var oldA = Number($("#alreadyVal").val());  //历史总数	
 	var newAmount = (oldA - oldgoodS + newAb).toFixed(0);  //新的总数量
 	var oldMoney = Number($("input[name=alreadyTotal]").val());  //历史总价
 	var oldGoodMoney = oldgoodS*singlePrice; //历史单品总价
 	var oldGM = (oldGoodMoney).toFixed(2);
 	var newGoodMoner = singlePrice*newAb;  //新的单品总价
 	var newGM = (newGoodMoner).toFixed(2);
 	var newMoney = (oldMoney - oldGoodMoney + newGoodMoner).toFixed(2);  //新的总价
 	//console.log("历史总价：" + oldMoney + "-历史单品总价" + oldGM + "-新的单品总价:" + newGM+"-新的总价："+ newMoney);
 	//console.log("历史总数："+ oldA + "-历史单品数量：" + oldgoodS + "-新的单品数量：" + newAb + "-新的总数：" + newAmount );
 	$("#amountLabel").text(newAmount);
 	$("#amount_2_label").text(newAmount);   //购物车总价显示
	$("#alreadyVal").val(newAmount);  //重置历史总数量
	$("#stockOut").text(newAb);  //外部数量记录--商品详情,单品
	$("#totalFooter").text(newMoney);  //写入新总价
	$("input[name=alreadyTotal]").val(newMoney); //重置历史总价 
	$("input[id=his_sinGoodT]").val(newGM);  //写入历史单品总价
	showOrHide();//新商品数量大于100	
}

/*购物车优惠券显示*/
function cart_coupon_show(oldgoodS,singlePrice,newAb){
	/*购物车优惠券显示控制*/
	var oldA = Number($("#alreadyVal").val());  //历史总数	
 	var newAmount = (oldA - oldgoodS + newAb).toFixed(0);  //新的总数量
 	var oldMoney = Number($("input[name=alreadyTotal]").val());  //历史总价
 	var oldGoodMoney = oldgoodS*singlePrice; //历史单品总价
 	var oldGM = (oldGoodMoney).toFixed(2);
 	var newGoodMoner = singlePrice*newAb;  //新的单品总价
 	var newGM = (newGoodMoner).toFixed(2);
 	var newMoney = (oldMoney - oldGoodMoney + newGoodMoner).toFixed(2);  //新的总价

	$(".fflist_coup input[type=checkbox]").removeAttr("checked");
	$(".fflist_coup>label").removeClass("activeLabel");
	if(newAmount == 0 || newAmount == ''||coupons.length==0){
	//if(newAmount == 0 || newAmount == ''){
		$("#ffCoup span").html('暂无可用优惠券');
	}else{
		$("#ffCoup span").html('有可用优惠券');
	}
	changeBtn(newMoney);  //购物车页面——结算按钮
}
/*分类*/
/*失去焦点后计算总价*/
function classPutTotal(oldgoodS,newAb){	
	var oldA = Number($("#alreadyVal").val());  //历史总数	
 	var newAmount = (oldA - oldgoodS + newAb).toFixed(0);  //新的总数量 
 
 	$("#amountLabel").text(newAmount);
	$("#alreadyVal").val(newAmount);  //重置历史总数量	
}
/*商品详情_规格变动价格变动*/
function freshTotal(thisP,amount){	
	var oldMoney = Number($("input[name=alreadyTotal]").val());  //历史总价
	var sinGodT = Number($("input[id=his_sinGoodT]").val());  //历史单品总价	
	var newGodT = amount*thisP;   //新的单品总价
	//console.log("历史总价：" + oldMoney + "历史单品总价："+ sinGodT + " -数量：" + amount + "新总价：" + newGodT);
	var reomMoney = oldMoney - sinGodT ;
	var newMoney = (reomMoney + newGodT).toFixed(2);   //新的总价
	//console.log("总价：" + newMoney + "-新：" + newMoney);

	$("input[name=alreadyTotal]").val(newMoney);//写入新历史总价
	$("#totalFooter").text(newMoney);//写入新总价
	$("input[id=his_sinGoodT]").val(newGodT);//写入新单品总价
}

/*限时抢购/热卖*/
/*加减数量按钮显示控制*/
$(function (){
	$("input[name='goodsAmount[]']").each(function (){
		$this = $(this);
		var goodsAmount = $this.val();
		//console.log("添加到购物车数量：" + goodsAmount);

		if(goodsAmount <= 0 || goodsAmount == ''){
			$this.parent().hide();
			$this.parent().parent().find(".addPop").show();
		}else{
			$this.parent().show();
			$this.parent().parent().find(".addPop").hide();
		}
	});	
});

/*购物车页面 -结算按钮*/
function changeBtn(newTotal){
	/*购物车按钮变化*/  
	var startP = Number($("#startP").text());//起送价
	if(startP > newTotal){		
		var farMoney = (startP - newTotal).toFixed(2);
		//console.log("起送价:" + startP + "-差价：" + farMoney);
		$("#totalBtn").addClass("greyBtn");
		$("#totalBtn").removeClass("redInput");
		$("#totalBtn").html('还差'+ farMoney +'元');
	}else{
		$("#totalBtn").removeClass("greyBtn");
		$("#totalBtn").addClass("redInput");
		$("#totalBtn").html('结算');
	}
}

/*购物车页面初始化*/
var totalAmount = 0;
var totalMoney = 0;
function reginalTot(){	
	$('input[name="goodsAmount[]"]').each(function (){
		var $this = $(this);
		var thisVal = Number($this.val());  //数量
		var sinP = Number($this.parent().parent().find(".singlePrice").text());  //单价
		var singTotal = (Number(thisVal * sinP)).toFixed(2);//单行总价
		//console.log("input值：" + thisVal + "-单价：" + sinP + "-单行总价：" + singTotal);
		getAmount(thisVal,singTotal);
	});	
}
function getAmount(sglAm,singTotal){	
	totalAmount += sglAm;
	var sglTot = Number(singTotal);	
	totalMoney += sglTot;	
	var holdTotalM = (totalMoney).toFixed(2);

	$("#amountLabel").text(totalAmount);  //banner 总数
	$("#amount_2_label").text(totalAmount);  //结算条总数
	$("#alreadyVal").val(totalAmount);  //结算条历史总数
	$("#totalFooter").text(holdTotalM); //结算条总价
	$("input[name=alreadyTotal]").val(holdTotalM);//结算条历史总价
	changeBtn(holdTotalM);  //按钮变动
	
	//console.log("总数：" + totalAmount + "-总价：" + totalMoney);
	if(holdTotalM =='' || holdTotalM <= 0||coupons.length==0){
		$("#ffCoup span").html("暂无可用优惠券");
	}else{
		$("#ffCoup span").html("有可用优惠券");
	}	
}









