var page = '';
var writing = false;

$(function () {

	// 每秒轮询
	/* setInterval(function () {
		$.ajax({
			type: "post",
			dataType: "json",
			url: ""
		});
	}, 60000); */

	// 菜单切换
	$(".nav-list a:not(.selected)").click(function () {
		var $_self = $(this);
		var menuId = $_self.data("nav_id");
		$_self.addClass("selected").parent().siblings().find("a").removeClass("selected");
		$(".left_menu").each(function () {
			var $_menu = $(this);
			if ($_menu.data("menu_id") == menuId) {
				$_menu.removeClass("hide");
				$_menu.parents(".menu-container").find(".menu-type").html($_self.html());
			}
			else {
				$_menu.addClass("hide");
			}
		});
	});

	// 初始化当前菜单，根据页面的 {$fpid} 匹配
	var currentPid = $("#navTab").data("current_id");
	$(".left_menu[data-menu_id=" + currentPid + "]").removeClass("hide");
	// 当前选中的菜单类型
	var $currentMenuType = $(".nav-list a[data-nav_id=" + currentPid + "]");
	$currentMenuType.addClass("selected");

	// 从模板获取隐藏域中的cookie前缀
	var cookiePrefix = $(".getCookiePrefix").val();

	$("#menu").find("dl").each(function () {
		var that = $(this);
		var id = that.attr("id");
		var status = $.cookie(cookiePrefix + "_admin_menu_" + id);
		if (status == "close") {
			that.children("dd").hide();
		} else {
			that.parents(".menu-container").find(".menu-type").html($currentMenuType.html());
			that.children("dd").show();
			that.addClass("open");
		}
	});

	$("#menu dt").click(function () {
		var dd = $(this).next();
		var parent = $(this).parent();
		var id = parent.attr("id");
		var status;
		if (dd.is(":hidden")) {
			status = "open";
			dd.show();
			parent.addClass("open");
		} else {
			dd.hide();
			status = "close";
			parent.removeClass("open");
		}
		$.cookie(cookiePrefix + "_admin_menu_" + id, status);
	});

	/*
	 * 菜单高亮代码
	 *
	 * */
	var menuIdValue = $(".getMenuId").val();
	if (menuIdValue) {
		$("#menu").find("a[data_id='" + menuIdValue + "']").addClass("selected");
	} else {
		$("#menu > div:not('.hide')").find("a").first().addClass("selected");
	}

	// 有警告数据的时候在前端页面显示
	if (warningObj && warningObj.length > 0) {
		var html = [
			'<div class="warning-box">'
		];
		$.each(warningObj, function (index) {
			var obj = warningObj[index];
			html.push('<div class="warning-list">' + obj.text);
			if (obj.url && obj.url.length > 0) {
				html.push('<a class="set-param" href="' + obj.url + '">前往设置</a>');
			}
			html.push('</div>')
		});
		html.push('</div>');
		$(".main-area .title-bar").before(html.join(''));
	}


	$(".defaultinput").each(function () {
		var defval = $(this).attr("_value");
		if (!$(this).val()) {
			$(this).val(defval).css('color', '#aaa');
		}
		$(this).focus(function () {
			if ($(this).val() == defval) {
				$(this).val('').css('color', '#333');
			}
		}).blur(function () {
			if ($(this).val() == '' || $(this).val() == defval) {
				$(this).val(defval).css('color', '#aaa');
			} else {
				$(this).css('color', '#333');
			}
		});
	});


	$("#sidebar").click(function () {
		if ($(this).hasClass("close")) {
			$("td#left,td#center").hide();
			$(this).text('打开左栏').removeClass("close").addClass("open");
		} else {
			$("td#left,td#center").show();
			$(this).text('关闭左栏').removeClass("open").addClass("close");
		}
	});


	$("#busy").change(function () {
		var status = $(this).attr("checked") ? 2 : 1;
		core.ajaxload({url: 'admin_status.asp?status=' + status});
	});


	$("form").submit(function () {
		$(this).find(".defaultinput").each(function () {
			if ($(this).val() == $(this).attr("_value")) {
				$(this).val('');
			}
		});
	});


	$("#clearcache").click(function () {
		core.ajaxload({
			url: 'updatecache.asp'
		});
	});


	$("input.checkall").click(function () {

		if ($(this).attr("checked")) {
			$("input.checkall").attr("checked", true);
			$(".id").attr("checked", true);
		} else {
			$("input.checkall").attr("checked", false);
			$(".id").attr("checked", false);
		}
	});


	// 这两行导致报错阻塞页面，暂时注销
	/*$("#navTab").tab({
	 cookie:true,
	 id:'navTab'

	 });*/

	if ($("#tabs").length > 0) {
		$("#tabs").tab({
			cookie: true
		});
	}


	$("input.input").focus(function () {
		writing = true;
		$(this).addClass("input_focus");
	}).blur(function () {
		writing = false;
		$(this).removeClass("input_focus");
	});


	$("textarea.textarea").focus(function () {
		writing = true;
		$(this).addClass("textarea_focus");
	}).blur(function () {
		writing = false;
		$(this).removeClass("textarea_focus");
	});


	$("table.mainColorFormTable").children("tbody").children("tr:odd").addClass("color");


	$("table.mainColormainListTable").children("tbody").children("tr:odd").addClass("color");

	$("table.main-table-stripe").children("tbody").children("tr:odd").addClass("style-stripe");

	// 判断是否有加了align = center 的td
	$(".main-table").each(function () {
		var $_table = $(this);
		$_table.find("td").each(function () {
			if ($(this).attr("align") == "center") {
				$(this).css({
					"text-align": "center"
				})
			}
		});
	});

//	$("table.mainColormainListTable tbody tr").live("mouseenter", function(){
//		$(this).addClass("hover");
//	}).live("mouseleave", function(){
//		$(this).removeClass("hover");
//	});


	$("button.tips").click(function () {
		var val = $(this).html();
		$.dialog({
			title: '帮助信息',
			content: val
		})
	});


});


function tips(t) {
	WE.tips.info({
		title: '帮助信息',
		content: t,
		ok: true
	});
}


$.fn.getids = function () {
	var ids = "";
	$(this).each(function () {
		if ($(this).attr("checked")) {
			ids += $(this).val() + ",";
		}
	});
	if (ids.length > 0) {
		ids = ids.substring(0, ids.length - 1);
	}
	return ids;
};

if (typeof artDialog != "undefined") {
	artDialog.notice = function (options) {
		var opt = options || {},
			api, aConfig, hide, wrap, top,
			duration = 800;

		var config = {
			left: '100%',
			top: '100%',
			fixed: true,
			drag: false,
			resize: false,
			follow: null,
			lock: false,
			init: function (here) {
				api = this;
				aConfig = api.config;
				wrap = api.DOM.wrap;
				top = parseInt(wrap[0].style.top);
				hide = top + wrap[0].offsetHeight;
				wrap.css('top', hide + 'px')
					.animate({top: top + 'px'}, duration, function () {
						opt.init && opt.init.call(api, here);
					});
			},
			close: function (here) {
				wrap.animate({top: hide + 'px'}, duration, function () {
					opt.close && opt.close.call(this, here);
					aConfig.close = $.noop;
					api.close();
				});

				return false;
			}
		};

		for (var i in opt) {
			if (config[i] === undefined) config[i] = opt[i];
		}
		return artDialog(config);
	};


	artDialog.fn.shake = function () {
		var style = this.DOM.wrap[0].style,
			p = [4, 8, 4, 0, -4, -8, -4, 0],
			fx = function () {
				style.marginLeft = p.shift() + 'px';
				if (p.length <= 0) {
					style.marginLeft = 0;
					clearInterval(timerId);
				}
			};
		p = p.concat(p.concat(p));
		timerId = setInterval(fx, 13);
		return this;
	};


}

$(function () {
	var $_body = $("body");

	// 全局表单提交事件
	// 公共异步提交，只需要在按钮添加 ajax-submit
	// $_body.on("click", ".ajax-submit", function () {
	$_body.on("click", ".ajax-submit", function (event) {
		var $_button = $(this);
		var $_form = $_button.parents("form");
		var text = $_button.val();
		$_button.attr("disabled", true).val("请稍后...");

		WE.post({
			data: $_form.serialize(),
			url: $_form.attr("action"),
			complete: function () {
				$_button.removeAttr("disabled").val(text);
				WE.hideWaiting();
			}
		});

		event.preventDefault(); // 防止表单重复提交

	});

	// 用户信息查看
	$_body.on("click", ".user-info-view", function () {
		var id = $(this).data("id");
		var url = $(".getUserUrl").val();
		$.dialog.open(url + "?id=" + id, {
			title: "用户信息",
			cancel: true,
			cancelVal: "关闭",
			width: 660,
			height: 300
		});
	});

	// 排序功能弹窗
	$("#rankDialog").click(function () {
		var $_self = $(this);
		$.dialog.open($_self.data("url") + "?model=" + $_self.data("model"), {
			title: "排序",
			width: 650,
			height: 300,
			cancel: true,
			ok: function () {
				var $_form = $($.dialog.data("sortForm"));
				// 填写数据
				var idsArray = [];
				$_form.find("option").each(function () {
					idsArray.push($(this).val());
				});
				$_form.find("input[name=ids]").val(idsArray.join(","));

				WE.post({
					url: $_self.data("url"),
					data: $_form.serialize()
				});
				return false;
			}
		})
	});

	// 全局提示方法
	var isAjaxRunning = false;
	$_body.on("click", ".show-tip", function () {
		if (isAjaxRunning) {
			return false;
		}
		var $_self = $(this);
		$_self.append('<span>获取中...</span>').addClass("requiring");
		isAjaxRunning = true;
		$.getJSON(tips_url + $_self.data("query") + "&jsoncallback=?", function (data) {
			isAjaxRunning = false;
			$_self.children().remove().removeClass("requiring");
			WE.tips.info({
				title: '帮助信息',
				content: '<div style="max-width: 400px;word-break: break-all">' + data.content + '</div>',
				cancel: true,
				cancelVal: "关闭",
				width: 400
			});
		});
	});

	// 全局初始化时间插件
	if ($('#starttime').length > 0) {
		$('#starttime').calendar();
		$('#endtime').calendar();
		/*$('#starttime:not(".activity")').add($('#endtime:not(".activity")')).focus(function(){
		 WdatePicker({skin:'ext',dateFmt:'yyyy-MM-dd',readOnly:true})
		 });*/

	}

	// 返回按钮事件
	var $_returnBtn = $(".return-btn");
	if ($_returnBtn.length > 0) {
		$_returnBtn.click(function () {
			var returnUrl = $(".getReferer").val();
			if (!returnUrl || returnUrl == "") {
				window.history.go(-1);
			} else {
				location.href = returnUrl;
			}
		});
	}

	// 订单页修改价格公共方法
	$(".order-edit-price").click(function () {
		$.dialog({
			title: "修改价格",
			content: $("#editPriceTpl")[0],
			cancel: true,
			ok: function () {
				var $_form = $("#editPriceTpl").find("form");
				WE.post({
					url: $_form.attr("action"),
					data: $_form.serialize()
				});
				return false;
			}
		});
	});
});




