/* 扩展WE对象 */
window.WE = window.WE || {};

// 以下follow uc的WE.js库
WE.constant = (function () {
	// 前后端数据接口中的默认 键名（键用于标识选中项）
	var defaultKeyName = "id";
	// 前后端数据接口中的默认 值名（值用于展示）
	var defaultValueName = "name";

	return {
		// 文本类
		DEFAULT_BUSY_TEXT: "服务器繁忙，请稍后重试！",
		DEFAULT_TIPS_TITLE: "消息",
		DEFAULT_TIPS_ERROR_TITLE: "错误提醒",
		DEFAULT_TIPS_LOADING_TITLE: "加载中",
		// 上传动作提示文本
		UPLOAD_FILE_ERROR_TEXT: "文件格式不符合或文件大小超限",
		// 上传后的图片，需要添加此前缀浏览
		UPLOAD_FILE_IMG_PREFIX: "http://uploads.webaapp.com/",

		// 配置类
		DATE_NORMAL_FORMAT: "yyyy-M-dd HH:mm:ss",
		DATA_ARRAY2MAP_DEFAULT_KEYNAME: defaultKeyName,
		DATA_ARRAY2MAP_DEFAULT_VALUENAME: defaultValueName,
		DEFAULT_TIPS_SHOW_DURATION: 3,
		DEFAULT_KEYNAME: defaultKeyName,
		DEFAULT_VALUENAME: defaultValueName,

		// TODO: fillFormByContainer/createRowByForm/resetForm (data-name)
		// TODO: $_weTips:id/className
	};
})();

/**
 * 有icon主题的，快捷artDialog提示框，包含 WE.tips.success、WE.tips.warn、WE.tips.error、WE.tips.info、WE.tips.showBusy、WE.tips.ask 方法。
 */
WE.tips = (function () {
	var tipsMethodsObj = {};
	var tipsTypeList = ["success", "warn", "error", "info"];

	$.each(tipsTypeList, function (i, tipsType) {
		tipsMethodsObj[tipsType] = function (options) {
			var settings = $.extend(true, {
				icon: "uc_" + tipsType,
				title: "提示信息",
				content:  "",
				subTipContent: "",
				// 已去掉皮肤样式中的默认padding，在此用js控制 提示框的padding
				padding: "48px 80px 40px 10px",
				time: WE.constant.DEFAULT_TIPS_SHOW_DURATION,
				close: $.noop
			}, options);

			// 如果指定subTipContent参数，则根据content的类型，将subTipContent追加至content后面
			if (settings.subTipContent) {
				var subTipContent = [
					'<p class="sub-tip">',
					settings.subTipContent,
					'</p>'
				].join("");

				// 字符串则拼接
				if (typeof settings.content == "string") {
					settings.content += subTipContent;
				}
				// 若是DOM元素，则使用新元素包裹content和subTipContent
				// （但这种情况下，当关闭弹窗时，原DOM元素被会新的div标签包裹，且subTipContent会被“带回”原DOM元素位置后面）
				else {
					var $_wrapper = $(settings.content).wrap("<div>").parent();

					$_wrapper.append(subTipContent);
					settings.content = $_wrapper[0];
				}
			}

			// 当设置 确定、取消或自定义按钮时，取消自动隐藏弹窗
			if (settings.ok || settings.cancel || settings.button) {
				settings.time = options.time || 0;
			}

			delete settings.subTipContent;

			return window.parent.$.dialog(settings);
		};
	});

	// 作为ajax请求失败时的提示
	tipsMethodsObj.showBusy = function (options) {
		return tipsMethodsObj.error($.extend({
			content: WE.constant.DEFAULT_BUSY_TEXT
		}, options));
	};

	// 询问提示框，底层调用了 WE.tips.success 实现。二次封装了询问icon、title、取消按钮，且不自动关闭
	tipsMethodsObj.ask = function (options) {
		return tipsMethodsObj.success($.extend({
			icon: "uc_ask",
			title: "确认？",
			cancel: true
		}, options));
	};

	return tipsMethodsObj;
})();

WE.date = (function () {
	var normalFormat = function (date) {
		return $.format.date(date, WE.constant.DATE_NORMAL_FORMAT);
	};
	/**

	 * @example 举些栗子
	 * // 几个典型应用场景：

	 // 没有值的单位 是否显示
	 WE.date.transformTime(18050); // -> "5小时00分钟50秒"
	 WE.date.transformTime(18050, {isShowEmptyUnit: 0}); // -> "5小时50秒"

	 // 补0的级别（特别注意：补0之后，第一个单位开头的0会被删除）
	 WE.date.transformTime(15815347, {padZeroLevel:"all"}); // -> "6个月03天01小时09分钟07秒"
	 WE.date.transformTime(15815347, {padZeroLevel:"year"}); // -> "6个月03天01小时09分钟07秒"
	 WE.date.transformTime(15815347, {padZeroLevel:"hour"}); // -> "6个月3天01小时09分钟07秒"
	 WE.date.transformTime(15815347, {padZeroLevel:"second"}); // -> "6个月3天1小时9分钟07秒"
	 WE.date.transformTime(15815347, {padZeroLevel:"none"}); // -> "6个月3天1小时9分钟7秒"

	 // 时间的最大展示单位
	 WE.date.transformTime(18050, {maxShowUnit: "minute"}); // -> "300分钟50秒"
	 WE.date.transformTime(18050, {maxShowUnit: "second"}); // -> "18050秒"
	 */
	var transformTime = function (timestamp, options) {
		var unitLevelMap = {
			"all": -1,
			"year": 0,
			"month": 1,
			"day": 2,
			"hour": 3,
			"minute": 4,
			"second": 5,
			"none": 6
		};

		var settings = $.extend({
			unitYear: "年",
			unitMonth: "个月",
			unitDay: "天",
			unitHour: "小时",
			unitMinute: "分钟",
			unitSecond: "秒",
			invalidTimeText: "-",
			isShowEmptyUnit: true,
			padZeroLevel: "minute",
			maxShowUnit: "all",
			useMillisecond: false
		}, options);

		// 如果timestamp不是数字，或timestamp小于等于0时，返回无效时间文本
		if ((!$.isNumeric(timestamp)) || (timestamp <= 0)) {
			return settings.invalidTimeText;
		}

		var timeSecond = timestamp;
		if (settings.useMillisecond) {
			timeSecond = Math.round(timestamp / 1000);
		}
		var timeArray = [];
		var timeUnits = [
			31536000,	// year	31536000 = 365 * 24 * 60 * 60
			2592000,	// month 2592000 = 30 * 24 * 60 * 60
			86400,		// day	 86400 = 24 * 60 * 60
			3600,		// hour	3600 = 60 * 60
			60,			// minute
			1			// second
		];
		var timeUnitsText = [
			settings.unitYear,
			settings.unitMonth,
			settings.unitDay,
			settings.unitHour,
			settings.unitMinute,
			settings.unitSecond
		];
		// 补0的级别（4代表 补齐 05分05秒）
		var padZeroLevel = unitLevelMap[settings.padZeroLevel];
		var showUnitLevel = unitLevelMap[settings.maxShowUnit];

		// 将时间数值及时间单位，填充到timeArray时间数组中
		$.each(timeUnits, function (i, timeUnit) {
			// 当i小于设置的 最大显示单位时，不计算时间（比如72小时不计算为3天）
			if (i < showUnitLevel) {
				return;
			}

			// 如果timeSecond小于 时间单位上限，且timeArray数组还没有元素，说明是时间间隔的开头部分，则不需要显示单位（比如 “0年0月3天5小时” 中的 “0年0月”）
			if ((timeSecond < timeUnit) && (timeArray.length == 0)) {
				return;
			}

			// 根据时间单位 得出商（即为 x年x月x日 中的x）
			var x = Math.floor(timeSecond / timeUnit);
			// 余数即为下次 timeSecond 需要计算的值
			timeSecond = timeSecond % timeUnit;

			// 根据padZeroLevel 对小于10的值 前面补 0（默认对“分钟”和“秒”补=0）
			if ((i >= padZeroLevel) && (x < 10)) {
				x = "0" + x;
			}

			// 当 x 为0时，说明当前单位没有值。结果是否显示，取决于settings.isShowEmptyUnit的值
			if ((Number(x) === 0) && !settings.isShowEmptyUnit) {
				return;
			}

			// 向结果的时间数组中 添加 需要显示的时间
			timeArray.push(x + timeUnitsText[i]);
		});

		// 将时间数组拼接起来 即为结果（如果没有结果，起始与结束时间相同）
		var resultTimeText = timeArray.join("");

		if (!resultTimeText) {
			resultTimeText = settings.invalidTimeText;
		}
		// 由于补0策略的存在，导致可能出现 第一个时间单位值小于10时而被补0的情况，所以这里slice掉
		else if (resultTimeText.indexOf("0") === 0) {
			resultTimeText = resultTimeText.slice(1);
		}

		return resultTimeText;
	};

	return {
		format: normalFormat,
		now: function () {
			return normalFormat(new Date());
		},
		transformTime: transformTime,
		// 根据一个开始时间和一个时间间隔，得到一个将来的时间。开始时间可以是时间字符串或时间戳，也可以省略，当省略时，默认使用当前时间
		getIncrementTime: function (incrementTime, baseTimestamp) {
			return Number(new Date(baseTimestamp || new Date())) + Number(incrementTime || 0);
		},
		// 根据一个开始时间和一个时间间隔，得到一个过去的时间。开始时间可以是时间字符串或时间戳，也可以省略，当省略时，默认使用当前时间
		getDecrementTime: function (decrementTime, baseTimestamp) {
			return Number(new Date(baseTimestamp || new Date())) - Number(decrementTime || 0);
		}
	};
})();

// 封装 WE.success、WE.warn、WE.error、WE.showBusy 方法。
// 调用方式： WE.success("hello world!", 5, function () { console.log("WE.success colsed.") });
(function () {
	var methodList = ["success", "warn", "error", "showBusy"];

	$.each(methodList, function (i, methodType) {
		WE[methodType] = function (text, duration, closedCallback) {
			var settings = {
				content: text,
				time: duration,
				close: closedCallback
			};

			return WE.tips[methodType](settings);
		};
	});
})();


// 因为依赖 #weWaitingTip 元素，所以将方法定义放在 $(function () {}) 中，等DOM加载完后就有 #weWaitingTip 元素
$(function () {
	// 【加载中】的提示框，用于异步请求过程中的展现
	var $_weWaitingTip = $("#weWaitingTip");
	WE.waiting = function (waitText, isMask) {
		$_weWaitingTip.find(".loading-tip-text").html(waitText);

		if(isMask){
			$_weWaitingTip.find('.we-loading-mask').show();
		}else{
			$_weWaitingTip.find('.we-loading-mask').hide();
		}
		// TODO: 根据 isMask 显示遮罩

		$_weWaitingTip.show();
	};
	WE.hideWaiting = function () {
		$_weWaitingTip.hide();
	};
});

/**
 * 封装业务型的快捷WE.ajax方法，使用方法及参数同$.ajax。
 */
WE.ajax = function (url, options) {
	// 照搬jquery的ajax方法的参数判断
	if (typeof url === "object") {
		options = url;
		url = undefined;
	}

	// 缓存 计算后的设置
	var isShowWaitTip = (options.isShowWaitTip !== false);

	// 默认ajax配置
	var settings= $.extend({
		url: url,
		type: "post",
		dataType: "json",
		error: function (data) {
			if (data) {
				WE.tips.error({
					title: "500错误",
					content: data.responseText,
					width: 500,
					cancel: true,
					cancelVal: "关闭"
				});
			}
			else {
				WE.showBusy();
			}
		},
		beforeSend: function() {
			WE.waiting('加载中，请稍后...',true);
		},
		// 注意，如果使用options.complete覆盖了这里的默认行为，则需要手动调用WE.hideWaiting()
		complete: function (jqXHR, textStatus) {
			if (isShowWaitTip) {
				WE.hideWaiting();
			}
		}
	}, options);
	// 删除扩展的参数
	delete settings.isCoverSuccess;
	delete settings.successResultFalse;
	delete settings.isSuccessShowTip;
	delete settings.isSuccessJump;
	delete settings.isResultFalseWarn;
	delete settings.waitText;
	delete settings.isShowWaitTip;
	delete settings.isShowWaitMask;
	delete settings.waitMaskStyle;

	// isCoverSuccess表示 是否覆盖增强的success方法。若值为true，则不使用增强的success方法。若值不为true，则使用增强的success方法。
	if (options.isCoverSuccess !== true) {
		// 增强的success方法：对响应数据的status做判断，并在错误时显示后端提示信息，正确时才调用原来的options.success
		settings.success = function (responseData, textStatus, jqXHR) {
			var context = this;
			// 缓存响应数据
			var responseDataText = responseData.text;
			var responseDataTime = responseData.time;

			// status 标识不成功时的处理
			if (!responseData.result) {
				// 对successResultFalse的 容错调用封装
				var resultFalseHandler = function () {
					var successResultFalse = options.successResultFalse;

					if ($.isFunction(successResultFalse)) {
						successResultFalse.call(context, responseData, textStatus, jqXHR);
					}
				};

				// 弹窗提示警告信息（当 options.isResultFalseWarn 配置不为 false 时执行）
				if (options.isResultFalseWarn !== false) {
					WE.warn(responseDataText, responseDataTime, function () {
						resultFalseHandler();
					});
				}
				else {
					resultFalseHandler();
				}

				return;
			}

			/*
			 * status标识成功时的处理（默认处理方式：先弹窗提示成功信息，再根据responseData的url或reload值 进行页面跳转或刷新）
			 */
			var successHandler = function () {
				var optionSuccess = options.success;
				var isJumpAfterCall = true;
				// 如果有传入options.success回调，则调用该方法
				if ($.isFunction(optionSuccess)) {
					isJumpAfterCall = optionSuccess.call(context, responseData, textStatus, jqXHR);
				}

				// 自动跳转（当 isSuccessJump 配置不为 false 且optionSuccess没有返回false 时执行）
				if ((options.isSuccessJump !== false) && (isJumpAfterCall !== false)) {
					// 若后端有返回url时，则跳转
					if (responseData.url) {
						setTimeout(function(){//解决IE高版本浏览器弹出白框问题
							window.location.href = responseData.url;
						},200);
					}
					// 若后端有返回reload时，则刷新
					else if (responseData.reload) {
						setTimeout(function(){//解决IE高版本浏览器弹出白框问题
							window.location.reload();
						},200);
					}
				}
			};

			// 弹窗提示成功信息（当 isSuccessShowTip 配置不为 false 时执行）
			if ((options.isSuccessShowTip !== false) && responseDataText) {
				// 使用成功提示框显示信息，并在指定时间后自动关闭
				WE.success(responseDataText, responseDataTime, function () {
					successHandler();
				});
			}
			else {
				successHandler();
			}
		};
	}

	// 发送ajax之前，根据配置 展示 waitTip
	if (isShowWaitTip) {
		WE.waiting(options.waitText, options.isShowWaitMask);
	}
	// 发送ajax
	return $.ajax(settings);
};
// 封装业务型的快捷 WE.get 和 WE.post 方法
$.each(["get", "post"], function (i, method) {
	WE[method] = function (url, options) {
		options = options || {};
		options.type = method;

		if (method == "get") {
			options.cache = false;
		}

		return WE.ajax(url, options);
	};
});
// 在 WE.ajax 的基础上，封装业务型的快捷 WE.jsonp 方法，调用时可以忽略业务参数要求。详细应用请参见 WE.ajax 的 example
WE.jsonp = function (url, options) {
	// 照搬jquery的ajax方法的参数判断
	if (typeof url === "object") {
		options = url;
		url = undefined;
	}

	// jsonp配置
	var settings= $.extend(true, {
		url: url,
		dataType: "jsonp",
		data: {
			"format": "jsonp"
		}
	}, options);

	// 分隔符，因为jsoncallback参数必须加在url后面才能生效（jQuery规则），所以需要根据原url参数 来决定使用“?”还是“&”分隔参数
	var separator = (settings.url.indexOf("?") == -1) ? "?" : "&";
	settings.url += separator + "jsoncallback=?";

	return WE.ajax(settings);

};

/**
 * @method createUploader 工厂方法，创建plupload插件的实例
 */
WE.plupload = (function () {

	var createUploader = function (options) {
		options = options || {};
		var noop = $.noop;
		var configs = $.extend({
			pluploadBasePath: WE.constant.STATIC_SOURCES_PATH + "/lib/plugin/plupload/",
			isAutoInit: true,
			isAutoUpload: true,
			isEasyGetFile: false,
			onFilesAdded: noop,
			onUploadProgress: noop,
			onFileUploaded: noop,
			onError: noop,
			isParseResponseJSON: true,
			previewImgElement: null
		}, options);

		// 删除 非plupload.Uploader创建的配置参数
		delete options.pluploadBasePath;
		delete options.isAutoInit;
		delete options.isAutoUpload;
		delete options.isEasyGetFile;
		delete options.onFilesAdded;
		delete options.onUploadProgress;
		delete options.onFileUploaded;
		delete options.onError;
		delete options.isParseResponseJSON;
		delete options.previewImgElement;

		var pluploadBasePath = configs.pluploadBasePath;
		// 以下默认配置是 new plupload.Uploader() 所支持的部分参数
		var settings = $.extend(true, {
			browse_button: "uploadFileButton",
			url: "/user/upload/",
			flash_swf_url: pluploadBasePath + "Moxie.swf",
			silverlight_xap_url: pluploadBasePath + "Moxie.xap",
			multi_selection: false,
			filters: {
				/* 上传限制的类型 */
				mime_types: [
					{
						title: "Image files",
						extensions: "jpg,png"
					}
				],
				max_file_size: "1mb",
				/* true时不允许队列中存在重复文件(启用后同一个文件上传过就不能再上传了，所以不适合，一定要false) */
				prevent_duplicates: false
			}
		}, options);

		var uploader = new plupload.Uploader(settings);

		// （大部分情况下需要）自动init，plupload插件在init之后才能绑定事件
		if (configs.isAutoInit) {
			uploader.init();

			// 对于图片预览元素的处理
			var $_previewImg = $(configs.previewImgElement);
			var isShowPreviewImg = !!$_previewImg.length;

			// 文件被添加后触发的事件
			uploader.bind("FilesAdded", function (uploader, files) {
				// 如果单文件上传模式，则将onFilesAdded回调的第二个参数设置为files数组的第一个元素（简化迭代过程）
				var addFile = (settings.multi_selection) ? files : files[0];
				// 如果是便捷模式，则在多文件上传模式下，用户只选择一个文件时，则将onFilesAdded回调的第二个参数设置为files数组的第一个元素（简化迭代过程）
				if (configs.isEasyGetFile && (files.length == 1)) {
					addFile = files[0];
				}
				configs.onFilesAdded.call(this, uploader, addFile);

				// 暂不支持多选文件模式的图片预览功能
				if (isShowPreviewImg && !settings.multi_selection) {
					setPreviewImage(addFile, $_previewImg);
				}

				if (configs.isAutoUpload) {
					// 设置自动上传
					uploader.start();
				}
			});
			// 文件上传进度事件
			uploader.bind("UploadProgress", configs.onUploadProgress);
			// 上传完成事件
			uploader.bind("FileUploaded", function (uploader, file, responseObject) {
				var response = responseObject.response;

				if (configs.isParseResponseJSON) {
					response = $.parseJSON(responseObject.response);
				}

				// 在原有三个参数的基础上，插入了第一个参数，值为反序列化的JSON对象
				configs.onFileUploaded.call(this, response, uploader, file, responseObject);
			});
			// 错误事件（当文件格式不对 或文件大小超限时 会触发）
			uploader.bind("Error", configs.onError);
		}

		return uploader;
	};

	/**
	 * @method previewImage 本地实时预览图片（可以在图片选中并上传时，根据本地图片显示预览图）
	 * plupload中为我们提供了mOxie对象，有关mOxie的介绍和说明可以看：https://github.com/moxiecode/moxie/wiki/API
	 * 本方法来自Github http://chaping.github.io/plupload/demo/index4.html
	 * @param {Object} file plupload事件监听函数参数中的file对象
	 * @param {function=} callback 预览图片准备完成的回调函数
	 * @param {Object=} options 配置项
	 * @param {boolean=true} options.isDownsize 是否压缩预览图
	 * @param {number=200} options.downsizeWidth 预览图的默认宽
	 * @param {number=200} options.downsizeHeight 预览图的默认高
	 * @param {function(file)=} options.unpreviewCallback 无法预览时（IE8及以下）的回调，会传入一个参数，为plupload事件监听函数参数中的file对象
	 *
	 * @example 举个栗子
	 * <!-- HTML结构 -->
	 <input type="text" id="uploadFileButton">
	 <img src="" alt="" id="imgPreview" />
	 // 监听添加文件的事件，然后显示预览图片
	 WE.plupload.createUploader({
			onFilesAdded: function (uploader, file) {
				WE.plupload.previewImage(file, function (imgsrc) {
					$("#imgPreview").attr("src", imgsrc);
				});
			}
		});
	 */
	var previewImage = function (file, callback, options) {
		var settings = $.extend({
			isDownsize: true,
			downsizeWidth: 200,
			downsizeHeight: 200,
			unpreviewCallback: function (file) {}
		}, options);

		// 确保文件是图片
		if (!file || !/image\//.test(file.type)) {
			return;
		}

		var fileSource = file.getSource();
		if (!file.loaded || !fileSource.size) {
			settings.unpreviewCallback(file);

			return;
		}

		// gif使用FileReader进行预览，因为mOxie.Image只支持jpg和png
		if (file.type == "image/gif") {
			var fr = new mOxie.FileReader();

			fr.onload = function () {
				if (callback) {
					callback(fr.result);
				}
				// 调用下面的析构方法，plupload插件内部会报错，所以这里去掉了
				// fr.destroy && fr.destroy();
				fr = null;
			};
			fr.readAsDataURL(file.getSource());
		}
		else {
			var preloader = new mOxie.Image();

			preloader.onload = function () {
				// 压缩要预览的图片
				if (settings.isDownsize) {
					preloader.downsize(settings.downsizeWidth, settings.downsizeHeight);
				}
				// 得到图片src，实质为一个base64编码的数据
				var imgsrc = (preloader.type == "image/jpeg") ? preloader.getAsDataURL("image/jpeg", 80) : preloader.getAsDataURL();

				if (callback) {
					// callback传入的参数为预览图片的url
					callback(imgsrc);
				}
				preloader.destroy && preloader.destroy();
				preloader = null;
			};
			preloader.load(fileSource);
		}
	};

	/**
	 * @method setPreviewImage （根据上传的本地文件来实时）设置预览图片。本方法对WE.plupload.previewImage进行再次封装，满足大部分情况下的便捷使用要求
	 * @param file {Object} plupload插件提供的file对象
	 * @param imgElement {string|HTMLElement|jQueryObject} 用来预览的图片元素
	 *
	 * @example 举个栗子
	 * <!-- HTML结构 -->
	 <input type="text" id="uploadFileButton">
	 <img src="" alt="" id="imgPreview" />
	 // 监听添加文件的事件，然后显示预览图片
	 WE.plupload.createUploader({
			onFilesAdded: function (uploader, file) {
				WE.plupload.setPreviewImage(file, $("#imgPreview"));
			}
		});
	 */
	var setPreviewImage = function (file, imgElement) {
		previewImage(file, function (imgsrc) {
			$(imgElement).attr("src", imgsrc);
		});
	};

	return {
		createUploader: createUploader,
		previewImage: previewImage,
		setPreviewImage: setPreviewImage
	};
})();

// 组件方法封装
WE.component = (function () {
	/**
	 * initNumber 对指定的input(type=text)渲染成带有 增加/减少按钮 的数字输入框组件（类似于HTML5的`<input type="number">`）
	 * @param options {Object} 配置项（其中，min、max、step、unit属性，可以覆盖对应的 元素上设置的 data-num_* 属性值。）
	 * @param options.inputSelector {string} 表单域选择器，该选择器指定的元素将被渲染成组件
	 * @param options.min {number=1|string} 输入框组件 可输入的最小值
	 * @param options.max {number=10000|string} 输入框组件 可输入的最大值
	 * @param options.step {number=1|string} 控制 输入框组件 增加/减少按钮 的增量
	 * @param options.unit {string=} 单位名称（附带显示，可选）
	 *
	 * @example 举些栗子
	 <!-- HTML结构 -->
	 <input type="text" class="we-number-input" name="testNumber" data-num_min="100" data-num_step="50">

	 // 默认配置
	 WE.component.initNumber();
	 // 自定义配置
	 WE.component.initNumber({
			// 下面的配置会覆盖 元素中的 data-num_* 属性值
			min: 10,
			unit: "个"
		});
	 */
	var initNumber = function (options) {
		var defaultConfigs = {
			inputSelector: ".we-number-input",
			min: 1,
			max: 10000,
			step: 1,
			unit: ""
		};
		var $_numberInput = $(defaultConfigs.inputSelector);

		$_numberInput.each(function () {
			var $_input = $(this);
			var settings = $.extend(true, {}, defaultConfigs,  {
				min: $_input.data("num_min"),
				max: $_input.data("num_max"),
				step: $_input.data("num_step"),
				unit: $_input.data("num_unit")
			}, options);

			// 初始化配置值
			var min = parseInt(settings.min);
			var max = parseInt(settings.max);
			var step = parseInt(settings.step);
			var unit = settings.unit;

			// 自动生成组件html结构
			var $_container = $([
				'<div class="we-number-container">',
				'<span class="number-input-box">',
				'<span class="we-number-unit">' + unit + '</span>',
				'</span>',
				'<span class="we-number-control">',
				'<span class="number-control-up"></span>',
				'<span class="number-control-down"></span>',
				'</span>',
				'</div>'
			].join(""));
			$_input.before($_container);
			// 由于在IE下两次wrap操作会导致input丢失，所以将原input克隆一份，之后移除该input；
			$_container.find(".number-input-box").prepend($_input.clone());
			$_input.remove();
			$_input = $_container.find(".we-number-input");

			// 查找当前input
			var findInput = function (element) {
				return $(element).parents(".we-number-container").find(settings.inputSelector);
			};
			// 限制值范围方法
			var verifyValue = function (element) {
				var $_targetInput = element ? findInput(element) : $_input;
				var inputValue = $_targetInput.val();
				var rangedValue = Math.max(Math.min(inputValue, max), min);

				$_targetInput.val(isNaN(rangedValue) ? min : rangedValue);
			};
			// 验证默认初始化值
			verifyValue();

			// 表单change事件，限制输入范围
			$_input.change(function () {
				verifyValue(this);
			});

			// 绑定数字增大和减小点击事件
			var $_increase = $_container.find(".number-control-up");
			var $_decrease = $_container.find(".number-control-down");
			$_increase.click(function () {
				var $_input = findInput(this);
				var value =  parseInt($_input.val());
				value += step;
				$_input.val(Math.min(value, max));
				$_input.trigger("change");
			});
			$_decrease.click(function () {
				var $_input = findInput(this);
				var value =  parseInt($_input.val());
				value -= step;
				$_input.val(Math.max(value, min));
				$_input.trigger("change");
			});

			// 输入框区域绑定自动对焦点击事件
			$(".number-input-box").click(function () {
				$(this).find($_input).focus();
			});
		});
	};

	var initTableTab = function (idSelector) {
		var $_tabContainer = $(idSelector);
		var currentClass = "table-tab--current";
		// 显示与 table-tab--current的tab_id一致的tab-relate
		var $_tabs = $_tabContainer.find("li");
		if ($_tabContainer.find("." + currentClass).length == 0) {
			$_tabContainer.find("[data-tab_id=1]").addClass(currentClass);
		}
		var initShowTabId = $_tabContainer.find("." + currentClass).data("tab_id");
		$("[data-tab_relate=" + initShowTabId + "]").show();

		// 点击切换tab
		$_tabs.click(function () {
			var $_self = $(this);
			if ($_self.hasClass(currentClass)) {
				return false;
			}

			$_self.addClass(currentClass).siblings().removeClass(currentClass);
			$(".tab-relate").hide();
			$("[data-tab_relate=" + $_self.data("tab_id") + "]").show();
		});
	};

	return {
		initNumber: initNumber,
		initTableTab : initTableTab
	};
})();