!function(t,n,e,i){var o=function(t,n){this.init(t,n)};o.prototype={init:function(t,n){this.ele=t,this.defaults={menu:[{text:"菜单一",callback:function(){}},{text:"菜单二",callback:function(){}}],target:function(t){},width:100,itemHeight:28,bgColor:"#fff",color:"#333",fontSize:14,hoverBgColor:"#f5f5f5"},this.opts=e.extend(!0,{},this.defaults,n),this.random=(new Date).getTime()+parseInt(1e3*Math.random()),this.eventBind()},renderMenu:function(){var t=this,n="#uiContextMenu_"+this.random;if(!(e(n).length>0)){var t=this,i='<ul class="ul-context-menu" id="uiContextMenu_'+this.random+'">';e.each(this.opts.menu,function(t,n){n.icon?i+='<li class="ui-context-menu-item"><a href="javascript:void(0);"><img class="icon" src="'+n.icon+'" /><span>'+n.text+"</span></a></li>":i+='<li class="ui-context-menu-item"><a href="javascript:void(0);"><span>'+n.text+"</span></a></li>"}),i+="</ul>",e("body").append(i).find(".ul-context-menu").hide(),this.initStyle(n),e(n).on("click",".ui-context-menu-item",function(n){t.menuItemClick(e(this)),n.stopPropagation()})}},initStyle:function(t){var n=this.opts;e(t).css({width:n.width,backgroundColor:n.bgColor}).find(".ui-context-menu-item a").css({color:n.color,fontSize:n.fontSize,height:n.itemHeight,lineHeight:n.itemHeight+"px"}).hover(function(){e(this).css({backgroundColor:n.hoverBgColor})},function(){e(this).css({backgroundColor:n.bgColor})})},menuItemClick:function(t){var n=this,e=t.index();t.parent(".ul-context-menu").hide(),n.opts.menu[e].callback&&"function"==typeof n.opts.menu[e].callback&&n.opts.menu[e].callback()},setPosition:function(t){e("#uiContextMenu_"+this.random).css({left:t.clientX+2,top:t.clientY+2}).show()},eventBind:function(){var t=this;this.ele.on("contextmenu",function(n){n.preventDefault(),t.renderMenu(),t.setPosition(n),t.opts.target&&"function"==typeof t.opts.target&&t.opts.target(e(this))}),e(n).on("click",function(){e(".ul-context-menu").hide()})}},e.fn.contextMenu=function(t){return new o(this,t),this}}(window,document,jQuery);
// scrollfix
//
var Shira;!function(t,e){var s;(s=t.ScrollFix||(t.ScrollFix={})).Watcher=function(t,i){this.element=t,this.options=e.extend({},s.Watcher.defaults,i),null===this.options.topFixOffset&&(this.options.topFixOffset=-this.options.topPosition),null===this.options.topUnfixOffset&&(this.options.topUnfixOffset=this.options.topPosition),null===this.options.bottomFixOffset&&(this.options.bottomFixOffset=-this.options.bottomPosition),null===this.options.bottomUnfixOffset&&(this.options.bottomUnfixOffset=this.options.bottomPosition),e(t).data("shira.scrollfix",this)},s.Watcher.defaults={topFixClass:"scrollfix-top",bottomFixClass:"scrollfix-bottom",substituteClass:"scrollfix-subtitute",topPosition:0,bottomPosition:0,topFixOffset:null,topUnfixOffset:null,bottomFixOffset:null,bottomUnfixOffset:null,syncSize:!0,syncPosition:!0,style:!0,styleSubstitute:!0,side:"top"},s.Watcher.prototype={element:null,substitute:null,options:null,fixed:!1,fixedAt:null,attached:!1,checkTop:!1,checkBottom:!1,getElementX:function(t){for(var i=0;i+=t.offsetLeft,t=t.offsetParent;);return i},getElementY:function(t){for(var i=0;i+=t.offsetTop,t=t.offsetParent;);return i},fix:function(t){var i,s;this.fixed||this.dispatchEvent("fix").isDefaultPrevented()||(i=e(this.element),s=e(this.element.cloneNode(!1)).addClass(this.options.substituteClass),this.options.styleSubstitute&&s.css("visibility","hidden").height("border-box"===i.css("box-sizing")?i.outerHeight():i.height()),this.substitute=s.insertAfter(this.element)[0],this.options.style&&(s={position:"fixed"},"top"===t?s.top=this.options.topPosition+"px":s.bottom=this.options.bottomPosition+"px",i.css(s)),i.addClass("top"===t?this.options.topFixClass:this.options.bottomFixClass),this.fixed=!0,this.fixedAt=t,this.dispatchEvent("fixed"))},updateFixed:function(){var t,i;this.options.syncSize&&e(this.element).width(e(this.substitute).width()),this.options.syncPosition&&(t=e(window).scrollLeft(),i=this.getElementX(this.substitute),e(this.element).css("left",i-t+"px")),this.dispatchEvent("update")},unfix:function(){var t;this.fixed&&!this.dispatchEvent("unfix").isDefaultPrevented()&&(e(this.substitute).remove(),this.substitute=null,t={},this.options.syncPosition&&(t.left=""),this.options.syncSize&&(t.width=""),this.options.style&&(t.position="",t[this.fixedAt]=""),e(this.element).css(t).removeClass("top"===this.fixedAt?this.options.topFixClass:this.options.bottomFixClass),this.fixed=!1,this.fixedAt=null,this.dispatchEvent("unfixed"))},attach:function(){var t;this.attached||((t=this).updateEventHandler=function(){t.pulse()},e(window).scroll(this.updateEventHandler).resize(this.updateEventHandler),this.attached=!0,this.pulse())},detach:function(){this.attached&&(this.unfix(),e(window).unbind("scroll",this.updateEventHandler).unbind("resize",this.updateEventHandler),this.attached=!1)},pulse:function(){var t=e(window),i=t.scrollTop(),t=i+t.height(),s=this.fixed?this.substitute:this.element,o=this.getElementY(s),s=o+e(s).outerHeight();this.fixed?"top"===this.fixedAt?i<=o-this.options.topUnfixOffset&&this.unfix():t>=s+this.options.bottomUnfixOffset&&this.unfix():("top"===this.options.side||"both"===this.options.side)&&i>o+this.options.topFixOffset?this.fix("top"):("bottom"===this.options.side||"both"===this.options.side)&&t<s-this.options.bottomFixOffset&&this.fix("bottom"),this.fixed&&this.updateFixed()},dispatchEvent:function(t){t=new e.Event(t+".shira.scrollfix",{watcher:this});return e(this.element).trigger(t),t}},e.fn.scrollFix=function(t){for(var i=0;i<this.length;++i)new s.Watcher(this[i],t).attach();return this}}(Shira=Shira||{},jQuery);

$.msg = function(response, callback) {
    $msg.removeAll();
    $_msg = $msg.error;
    if (typeof response == 'object') {
        if (response.code == 1) {
            $_msg = $msg.success;
        } else if (response.code == 0) {
            $_msg = $msg.error;
        } else {
            $_msg = $msg.warning;
        }
        $_msg(response.msg, function() {
            if (callback) {
                callback();
            }
            if (response.target == '#') {
                window.location.reload();
            } else if (response.target.length > 0) {
                window.location.href = response.target;
            }
            $.simmask.hide();
        })
    } else {
        $_msg('Response Error!', 3000, function() {
            $.simmask.hide();
        })
    }
}
$(function(){
    $("body").on('focus', 'input[date]', function(event) {
        $(this).datepicker({
            multidate: false,
            format: 'yyyy-mm-dd'
        });
    });

});