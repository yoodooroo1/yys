/*账户安全与设置*/

/*---------修改密码------------*/
function ifChange(){

	var tel = $("#userTel").html();
	if(tel.length < 11){
		goTelFirst();  //未绑定手机
	}else{
		window.location.href = 'changePass.html';  //跳转至修改密码界面
	}
}
function goTelFirst(){
	layer.msg('请先绑定手机',{
		shift:2,
		time:1500,
		area:'134px'
	});
} 
/*-------------绑定手机----------------*/ 
function bindTel(){
	var tel = $("#userTel").html();
	
	if(tel.length < 11){
		
              window.location.replace("http://"+window.location.host+"/index.php?c=Member&a=bindtel&se="+store_id+"&f="+f);

		//window.location.href = "http://"+window.location.host+"/index.php?c=Member&a=bindtel&se="+store_id+"&f="+f; 

	}else{
		changeBTel(tel);
	}
}
function changeBTel(tel){
	layer.open({
		type:1,
		title:false,
		closeBtn:false,
		area:['78%','105px'],
		offset:'40%',
		content:'<div class="exPop_cons"><p class="normalLine btnCenter changeTelTop">当前已绑定手机号<font class="textColorRed">'+tel+'</font>，是否要更换手机？</p>'+
		'<div class="exBtn_con"><input name="cancel" type="button" value="取消" class="textRInput"/><input name="exchange" type="button" value="更换" class="redInput leftBtn"/></div>'+
		'</div>',
		success:function (layero,index){
			$(".layui-layer-shade").click(function (){
		        layer.close(index); 
		    });
			$("input[name=cancel]").click(function (){
		        layer.close(index); 
		    });
		    $("input[name=exchange]").click(function (){
		        layer.close(index); 
		       // window.location.href = "http://"+window.location.host+"/index.php?c=Member&a=bindtel&se="+store_id+"&f="+f;  
		        window.location.replace("http://"+window.location.host+"/index.php?c=Member&a=bindtel&se="+store_id+"&f="+f);
				  
		    });
		}
	});
}



/*修改密码*/
function checkRest(){
	var olderP = $("input[name=older]").val();
	var old  = $("input[name=oldPassw]").val();
	var newPass = $("input[name=newPass_fir]").val();
	var rePass = $("input[name=newPass_sec]").val();
	//console.log("旧密码：" + olderP + "-获取的旧密码：" + old + "-新密码：" + newPass + "-确认密码：" + rePass);
	if(old == '' || old.length == 0){
		need_pass('旧密码');
		return;
	}else if(newPass == '' || newPass.length == 0){
		need_pass('新密码');
		return;
	}else if(rePass == '' || rePass.length == 0){
		need_pass('确认密码');
		return;
	}else if(olderP != old){
		diffOld();
		return;
	}else if(newPass != rePass){
		diffNew();
		return;
	}else{
		successNew();
		setTimeout(function (){
			window.location.href = 'user.html';
		},1000);
	}
}
function need_pass(word){
	layer.msg('请输入'+ word +'',{
		shift:2,
		time:1500,
		area:'148px'
	});
}
function need_pass2(word){
	layer.msg( word ,{
		shift:2,
		time:1500,
		area:'148px'
	});
}
function diffOld(){
	layer.msg('输入的旧密码错误，请重输',{
		shift:2,
		time:1500,
		area:'218px'
	});
}
function diffNew(){
	layer.msg('两次输入的新密码不同',{
		shift:2,
		time:1500,
		area:'218px'
	});
}
function successNew(){
	layer.msg('您的新密码修改成功',{
		shift:2,
		time:1500,
		area:'218px'
	});
}

function foucsCss(){
	var old = $("input[name=oldPassw]");
	var newp = $("input[name=newPass_fir]");
	var secP = $("input[name=newPass_sec]");
	old.focus(function (){
		old.parent().css('border-bottom','1px solid #d83838');	
	});
	old.blur(function (){
		old.parent().css('border-bottom','0');	
	});
	newp.focus(function (){
		newp.parent().css('border-bottom','1px solid #d83838');	
	});
	newp.blur(function (){
		newp.parent().css('border-bottom','0');	
	});
	secP.focus(function (){
		secP.parent().css('border-bottom','1px solid #d83838');	
	});
	secP.blur(function (){
		secP.parent().css('border-bottom','0');	
	});
}

/*绑定手机*/
function bindInputCss(){
	var inputTel = $("input[name=binded_tel]");
	var telCode = $("input[name=telCode]");
	inputTel.focus(function (){
		$(this).parent().css('border-bottom','1px solid #d83838');
	});
	inputTel.blur(function (){
		$(this).parent().css('border-bottom','1px solid #efefef');
	});
	telCode.focus(function (){
		$(this).parent().css('border-bottom','1px solid #d83838');
	});
	telCode.blur(function (){
		$(this).parent().css('border-bottom','1px solid #efefef');
	});
}
/*-----------验证----------*/
function checkTel(){
	var inputTel = $("input[name=binded_tel]").val();
	if(inputTel == '' || inputTel.length == 0){
		need_pass('手机号');
		return;
	}else if(inputTel.length != 11){
		falseTel();
	}else{
		checking();
		
		setTimeout(function (){
			
			//sendMessage(inputTel);
			 $.ajax({			 
					 url:xxapi+"/xxapi/index.php?act=sms_verification&op=appover_sms",   
					 type:'post',   
					 data:'tel='+inputTel+"&channel_id="+channel_id,   
					 async : false, //默认为true 异步   
					 error:function(XMLHttpRequest, textStatus, errorThrown){   
						   //alert(XMLHttpRequest.status+"111");
						$("#page_1").hide();		
			            $("#page_2").show();
			            $("#page_tel").text(inputTel);  //传电话号码
		                sendMessage();
					 },   
					 success:function(data){
						 
					 }
				   });

		},1000);//绑定手机下一步页面,1000与验证时间一致
	}
}
/*-----正在验证------*/
function checking(){
	layer.open({
		type:3,
		content:'<div class="checkingText">验证中...</div>',
		area:'80px',
		time:1000
	});
}
function falseTel(){
	layer.msg('手机号码错误',{
		shift:2,
		time:1500,
		area:'218px'
	});
}
/*下一步 验证*/
function checkedCode(){
	var inputTel = $("input[name=binded_tel]").val();
	var code = $("input[name=telCode]").val();
	var reg_pass = 0;
	if(code == '' || code.length == 0){
		need_pass('验证码');
		return;
	}
	if(firstlogin==1){
		var reg_pass = $("input[name=reg_pass]").val();
		if(reg_pass.length<6){
          need_pass2('密码长度不能小于6位');
          return;
		}
	}
   
       $.ajax({			 
			 url:"http://"+window.location.host+"/index.php?c=Member&a=opbindtel",  
			 type:'post',   
			 data:'tel='+inputTel+"&code="+code+"&password="+reg_pass+"&se="+store_id+"&f="+f,   
			 async : false, //默认为true 异步   
			 error:function(XMLHttpRequest, textStatus, errorThrown){   
				   alert(XMLHttpRequest.status);
				// $("#page_1").hide();		
	   //          $("#page_2").show();
	   //          $("#page_tel").text(inputTel);  //传电话号码
    //             sendMessage(inputTel);
			 },   
			 success:function(data){
			 	
				if (data==0) {
					  successCode();
		              setTimeout(function (){
			          // window.location.href = 'user.html';
			          window.location.replace("http://"+window.location.host+"/index.php?c=Member&a=personnelSet&se="+store_id+"&f="+f);
		              },1000);

				}else{
					need_pass2(data);
				}
				
			 }
		   });


	
}
function successCode(){
	layer.msg('已成功绑定该手机',{
		shift:2,
		time:1500,
		area:'218px'
	});
}
/*登录/注册*/

/*-----已注册-密码验证-----*/
function next_pass(){
	var pasW = $("input[name=login_pass]").val();
	if(pasW == '' || pasW.length == 0 ){
		need_pass('密码');
		return;
	}else{
		window.location.href = 'index.html';   //输入密码成功，跳到首页
	}
}
/*--------未注册-密码验证码-------*/
function checkCodePass(){
	var passCode = $('input[name="telCode"]').val();
	var password = $('input[name="reg_pass"]').val();
	
	if(passCode == '' || passCode.length == 0){
		need_pass('验证码');
		return;
	}else if(password == '' || password.length == 0){
		need_pass('密码');
		return;
	}else{
		window.location.href = 'index.html';  //注册完进首页
	}
}

/*-------更换手机------*/
function changeTel_reg(){
	$("#reg_2").hide();
	$("#reg_3").hide();
	$("#reg_1").show();
	$("input[name=tel_reg]").val('');
}
/*---------忘记密码------*/
function forgetPass(){
	var tel = $("input[name=tel_reg]").val();
	var gb_no = $("#gb_country_no").text();

	$("#reg_2").hide();
	$("#reg_1").hide();
	$("#reg_3").show();
	$("#reg_3_tel").html(gb_no + ' ' + tel); //传电话
	sendMessage();//发验证码
}


/*编辑地址*/
function input_add(){
	var name = $("input[name=name]");
	var tel = $("input[name=tel]");
	var add = $("textarea[name=detail_add]");
	var province = $("#s_province");
	var city = $("#s_city");
	var country = $("#s_county");
	name.focus(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #d83838')
	});
	name.blur(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #efefef')
	});
	tel.focus(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #d83838')
	});
	tel.blur(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #efefef')
	});
	add.focus(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #d83838')
	});
	add.blur(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #efefef')
	});
		province.focus(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #d83838')
	});
	province.blur(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #efefef')
	});
	city.focus(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #d83838')
	});
	city.blur(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #efefef')
	});
	country.focus(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #d83838')
	});
	country.blur(function (){
		$this = $(this);
		$this.parent().parent().css('border-bottom','1px solid #efefef')
	});
}

/*面对面支付*/
function ff_input(){
	var inpt = $("input[name=money]");
	var text_ff = $(".ffTextarea textarea");
	inpt.focus(function (){
		$this = $(this);
		$this.parent().css('border','1px solid #d83838')
	});
	inpt.blur(function (){
		$this = $(this);
		$this.parent().css('border','0')
	});
	text_ff.focus(function (){
		$this = $(this);
		$this.parent().css('border','1px solid #d83838')
	});
	text_ff.blur(function (){
		$this = $(this);
		$this.parent().css('border','0')
	});
}