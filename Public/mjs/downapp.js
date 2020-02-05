     var os = function() {
     var ua = navigator.userAgent,
     isWindowsPhone = /(?:Windows Phone)/.test(ua),
     isSymbian = /(?:SymbianOS)/.test(ua) || isWindowsPhone, 
     isAndroid = /(?:Android)/.test(ua), 
     isFireFox = /(?:Firefox)/.test(ua), 
     isChrome = /(?:Chrome|CriOS)/.test(ua),
     isIpad = /(?:iPad|PlayBook)/.test(ua), 
     isTablet = /(?:iPad|PlayBook)/.test(ua)||(isFireFox && /(?:Tablet)/.test(ua)),
     isSafari = /(?:Safari)/.test(ua),
     isIPhone = /(?:iPhone)/.test(ua) && !isTablet,
     isOpen= /(?:Opera Mini)/.test(ua),
     isUC = /(?:UCWEB|UCBrowser)/.test(ua),
     isWeiXin = /(?:MicroMessenger)/.test(ua),
   isQQ = /(?:QQ)/.test(ua),
     isPc = !isIPhone && !isAndroid && !isSymbian;
     return {
          
          isTablet: isTablet,
          isIPhone: isIPhone,
          isAndroid : isAndroid,
          isPc : isPc,
          isOpen : isOpen,
          isUC: isUC,
      isWeiXin: isWeiXin,
          isIpad : isIpad,
      isQQ : isQQ,
     };
}();

  function downloadApp() {
    // var ifr = document.createElement('iframe');  
    //     ifr.src = 'm://www.xunxin.com';  
    //     ifr.style.display = 'none';     
    //     var startTime = +new Date(); 
    //     document.body.appendChild(ifr);  
    //     var ret = window.setTimeout(function() { // 如果不能打开客户端，跳到下载页面  
    //         if (Date.now() - startTime < 440) // 通过判断触发的时间与执行settimeout的时间差值是否小于设置的定时时间加上一个浮动值（一般设为100）。  
    //             {   
                   if (os.isWeiXin) {
                    document.getElementById('pop-div').style.display='block';
                    return true;
                   } else{
            if(os.isIPhone || os.isIpad){
              window.location="http://itunes.apple.com/cn/app/xun-xin/id1018561898?mt=8";
            } else {
              window.location="http://dl.wlsd.com.cn/apps/XunXinCnt.apk";
            } 
                   }
        //        }
        // }, 400); 
    return false;
  }
  $("#pop-div").on('click',function (){ 
      document.getElementById('pop-div').style.display='none';
    });