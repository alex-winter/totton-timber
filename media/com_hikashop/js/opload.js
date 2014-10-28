/**
 * @package    HikaShop for Joomla!
 * @version    2.3.4
 * @author     hikashop.com
 * @copyright  (C) 2010-2014 HIKARI SOFTWARE. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/**
 * Opload v 1.0.0
 * Copyright (c) 2010-2013 Jerome Glatigny
 */
(function () {
	window.oploaders = {};
	var opload = function(name, url, file, options) {
		var d = document;
		this.name = name.replace(' ', '_').replace('"','_').replace("'",'_');
		this.url = url;
		this.options = options;
		this.filesUpload = d.getElementById(file);
		this.filesUpload.setAttribute('tabIndex', '-1');
		this.filesUploadTemplate = this.filesUpload.cloneNode(true);
		this.files = {};
		this.fileCounter = 0;

		this.ieVer = -1;
		if(navigator.appName == 'Microsoft Internet Explorer') {
			var ua = navigator.userAgent, re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
			if(re.exec(ua) != null)
				this.ieVer = parseFloat(RegExp.$1);
		}

		this.data = options.data || null;
		this.callbacks = options.callbacks || null;

		var dropZone = options.drop || null;
		if(dropZone) this.dropArea = d.getElementById(dropZone);

		var list = options.list || null;
		if(list) this.fileList = d.getElementById(list);

		if(this.options.hideInput === undefined)
			this.options.hideInput = true;

		this.init();
		window.oploaders[this.name] = this;
		return this;
	};

	opload.prototype = {
		init: function(){
			var t = this;

			t.initInput();

			if(t.dropArea && t.dropArea.addEventListener && (t.ieVer < 0 || t.ieVer > 9.0)) {
				t.dropArea.addEventListener("dragleave", function (evt) {
					var target = evt.target;
					if (target && target === t.dropArea) {
						this.className = "opload-drop-zone";
					}
					evt.preventDefault();
					evt.stopPropagation();
				}, false);

				t.dropArea.addEventListener("dragenter", function (evt) {
					this.className = "opload-drop-zone opload-drop-over";
					evt.preventDefault();
					evt.stopPropagation();
				}, false);

				t.dropArea.addEventListener("dragover", function (evt) {
					evt.preventDefault();
					evt.stopPropagation();
				}, false);

				t.dropArea.addEventListener("drop", function (evt) {
					t.traverseFiles(evt.dataTransfer.files);
					this.className = "opload-drop-zone";
					evt.preventDefault();
					evt.stopPropagation();
				}, false);

				if(t.options.hideInput) {
					t.filesUpload.style.display = 'none';
					t.filesUploadTemplate.style.display = 'none';
				}
			} else if(t.dropArea && !t.options.forceDrop) {
				t.dropArea.style.display = 'none';
			}

			if(t.ieVer >= 9.0 && t.ieVer < 10.0 && t.options.hideInput) {
				t.filesUpload.style.display = 'none';
				t.filesUploadTemplate.style.display = 'none';
			}
		},
		initInput: function() {
			var t = this;
			if(t.filesUpload.addEventListener) {
				t.filesUpload.addEventListener("change", function () {
					t.traverseFiles(this.files, this);
				}, false);
			} else {
				t.filesUpload.attachEvent('onchange', function() {
					t.traverseFiles(this.files, t.filesUpload);
				});
			}

			if(t.ieVer > 0 && t.ieVer <= 8.0) {
				var btn = document.getElementById(t.filesUpload.getAttribute('id') + '-btn');
				btn.style.position = 'relative';
				btn.style.overflow = 'hidden';
				t.filesUpload.style.position = 'absolute';
				t.filesUpload.style.top = '0px';
				t.filesUpload.style.right = '0px';
				t.filesUpload.style.margin = '0px';
				t.filesUpload.style.opacity = '0';
				t.filesUpload.style.filter = 'alpha(opacity=0)';
				t.filesUpload.style.cursor = 'hand';
			}
		},
		upload: function(file){
			var d = document, t = this,
				entryId = t.fileCounter++,
				entry = {
					div: d.createElement("div"),
					progressBar: null,
					progressPercentage: null,
					uploadThumb: null,
					xhr: null,
					id: entryId
				},
				reader, fileInfo, formData, fileSize;

			// Present file info and append it to the list of files
			var template = '<div id="opload_'+this.name+'_'+entryId+'" class="oploadQueueItem"><div class="oploadThumb"></div><a class="cancel" href="#cancel" onclick="window.oploaders[\''+this.name+'\'].cancel(this, '+entryId+'); return false;">×</a>'+
				'<span class="fileName">{FILENAME} ({FILESIZE})</span><span class="oploadPercentage"></span>'+
				'<div class="oploadProgress"><div class="oploadProgressBar active"></div></div></div>';

			if(this.options.template)
				template = this.options.template.replace('{NAME}', this.name).replace('{ID}', entryId);

			if(file.size >= 1073741824)
				fileSize = (file.size / 1073741824).toFixed(2) + '&nbsp;GB';
			else if (file.size >= 1048576)
				fileSize = (file.size / 1048576).toFixed(2) + '&nbsp;MB';
			else
				fileSize = (file.size / 1024).toFixed(2) + '&nbsp;KB';

			var fileName = file.name;
			if(t.options.maxFilenameSize && fileName.length > t.options.maxFilenameSize) {
				if(!t.options.truncateBegin) t.options.truncateBegin = 12;
				if(!t.options.truncateEnd) t.options.truncateEnd = 6;
				fileName = fileName.substring(0, t.options.truncateBegin) + '...' + fileName.substring(fileName.length - t.options.truncateEnd);
			}

			fileInfo = template.replace('{FILENAME}', fileName).replace('{FILESIZE}', fileSize);
			entry.div.innerHTML = fileInfo;

			for(var c in entry.div.childNodes[0].childNodes) {
				var cn = entry.div.childNodes[0].childNodes[c].className;
				if(cn == 'oploadPercentage')
					entry.progressPercentage = entry.div.childNodes[0].childNodes[c];
				if(cn == 'oploadProgress')
					entry.progressBar = entry.div.childNodes[0].childNodes[c].childNodes[0];
				if(cn == 'oploadThumb')
					entry.uploadThumb = entry.div.childNodes[0].childNodes[c];
			}

			// If the file is an image and the web browser supports FileReader,
			// present a preview in the file list
			//
			if (typeof FileReader !== "undefined" && (/image/i).test(file.type)) {
				var img = document.createElement("img");
				if(entry.uploadThumb)
					entry.uploadThumb.appendChild(img);
				reader = new FileReader();
				reader.onload = (function (theImg) {
					return function (evt) {
						theImg.src = evt.target.result;
					};
				}(img));
				reader.readAsDataURL(file);
			}

			//
			//
			if(t.options.maxSize !== undefined && t.options.maxSize > 0 && file.size > this.options.maxSize) {
				entry.div.childNodes[0].className += ' oploadError';
				entry.status = 1;
				entry.progressBar.style.width = '100%';
				if(t.options.maxSize >= 1073741824) maxSize = (t.options.maxSize / 1073741824).toFixed(2) + '&nbsp;GB';
				else if (t.options.maxSize >= 1048576) maxSize = (t.options.maxSize / 1048576).toFixed(2) + '&nbsp;MB';
				else maxSize = (t.options.maxSize / 1024).toFixed(2) + '&nbsp;KB';
				if(entry.progressPercentage)
					entry.progressPercentage.innerHTML = ' Limit: ' + maxSize;
				window.Oby.removeClass(entry.progressBar, 'active');
				t.files[entryId] = entry;
				t.fileList.appendChild(entry.div);
				return false;
			}

			// Uploading - for Firefox, Google Chrome and Safari
			entry.xhr = new XMLHttpRequest();

			// Update progress bar
			entry.xhr.upload.addEventListener("progress", function (evt) {
				if (evt.lengthComputable) {
					var p = (evt.loaded / evt.total) * 100 + "%";
					if(entry.progressBar)
						entry.progressBar.style.width = p;
					if(entry.progressPercentage)
						entry.progressPercentage.innerHTML = p;
				} else {
					// No data to calculate on
				}
			}, false);

			// File uploaded
			entry.xhr.addEventListener("load", function () {
				var response = true;
				if(!entry.xhr.responseText || entry.xhr.responseText.length == 0 || entry.xhr.responseText == '0')
					response = false;
				if(t.callbacks && t.callbacks.done) {
					response = t.callbacks.done(entry);
				}
				if(entry.progressBar) {
					if(response)
						entry.div.childNodes[0].className += ' oploadFinish';
					else
						entry.div.childNodes[0].className += ' oploadError';
					entry.progressBar.style.width = '100%';
					window.Oby.removeClass(entry.progressBar, 'active');
				}
				if(entry.progressPercentage)
					entry.progressPercentage.innerText = '100%';
				entry.status = 1;
			}, false);

			entry.xhr.open("POST", t.url, true);

			// Set appropriate headers / Does not work good with all browsers
			// entry.xhr.setRequestHeader("Content-Type", "multipart/form-data");

			formData = new FormData();
			if(t.data) {
				for(var k in t.data) {
					if(t.data[k] && t.data.hasOwnProperty(k))
						formData.append(k, t.data[k]);
				}
			}
			formData.append(file.name, file);

			// Send the file (doh)
			entry.xhr.send(formData);
			entry.status = 0;

			t.files[entryId] = entry;
			t.fileList.appendChild(entry.div);
		},
		uploadFallback: function(input) {
			var gen = function(html) {
				var d = document.createElement('div'), e;
				d.innerHTML = html;
				e = d.firstChild;
				d.removeChild(e);
				delete d; d = null;
				return e;
			};

			var d = document,
				t = this,
				entryId = t.fileCounter++,
				entry = {
					div: d.createElement("div"),
					status: 0,
					input: input,
					id: entryId
				},
				iframeOnLoad = null,
				filename = 'noname',
				iframe = gen('<iframe src="javascript:false;" id="oploader_fallback_'+entryId+'" name="oploader_fallback_' + entryId + '" style="display:none;"/>'),
				form = gen('<form method="POST" enctype="multipart/form-data" target="oploader_fallback_'+entryId+'" action="'+this.url+'" style="position:absolute;left:-9999px;top:-9999px;"><input type="submit"/></form>'),
				template = '<div id="opload_'+t.name+'_'+entryId+'" class="oploadQueueItem"><div class="oploadThumb"></div>'+
					'<a class="cancel" href="#cancel" onclick="window.oploaders[\''+t.name+'\'].cancel(this, '+entryId+'); return false;">×</a>'+
					'<span class="fileName">{FILENAME}</span>'+
					'<div class="oploadProgress"><div class="oploadProgressBar active" style="width:100%"></div></div></div>';

			if(input && input.value)
				filename = input.value.replace(/.*(\/|\\)/, "");
			entry.div.innerHTML = template.replace('{FILENAME}', filename);

			for(var c in entry.div.childNodes[0].childNodes) {
				var cn = entry.div.childNodes[0].childNodes[c].className;
				if(cn == 'uploadProgress')
					entry.progressBar = entry.div.childNodes[0].childNodes[c].childNodes[0];
			}

			// Build form
			var i = d.createElement('input');
			i.type = 'hidden'; i.name = 'opload-mode'; i.value = 'fallback';
			form.appendChild(i);
			if(t.data) {
				for(var k in t.data) {
					if(t.data[k] && t.data.hasOwnProperty(k)) {
						i = d.createElement('input');
						i.type = 'hidden'; i.name = k; i.value = t.data[k];
						form.appendChild(i);
					}
				}
			}
			input.name = 'file_' + entryId;
			form.appendChild(input);
			d.body.appendChild(iframe);
			d.body.appendChild(form);

			// Attach "load" event to the form
			iframeOnLoad = function() {
				if(!iframe.parentNode) return;
				var response = false;
				try{
					if(iframe.contentDocument && iframe.contentDocument.body && iframe.contentDocument.body.innerHTML == 'false')
						return;

					//
					// Upload is finished : Get iframe content
					//
					var doc = iframe.contentDocument ? iframe.contentDocument: iframe.contentWindow.document,
						innerHTML = doc.body.innerHTML;
					response = true;
					if(innerHTML && innerHTML.length > 0) {
						if(innerHTML.substr(0, 5) == '<PRE>') {
							innerHTML = innerHTML.substr(5, innerHTML.length - 11);
							innerHTML = innerHTML.replace(/&gt;/g, '>').replace(/&lt;/g, '<').replace(/&amp;/g, '&');
						}
					}
					if(!innerHTML || innerHTML.length == 0 || innerHTML == '0')
						response = false;
					if(t.callbacks && t.callbacks.done) {
						response = t.callbacks.done(entry, innerHTML);
					}
				}catch(e){};

				entry.status = 1;
				entry.input = null;
				if(entry.progressBar) {
					entry.div.childNodes[0].className += response ? ' oploadFinish' : ' oploadError';
					entry.progressBar.style.width = '100%';
					window.Oby.removeClass(entry.progressBar, 'active');
				}
				input.name = '';
				input.value = '';

				setTimeout(function(){
					if(iframe.removeEventListener)
						iframe.removeEventListener('load', iframeOnLoad);
					else
						iframe.detachEvent('onload', iframeOnLoad);
					iframe.parentNode.removeChild(iframe);
				}, 10);
			};
			if(iframe.addEventListener)
				iframe.addEventListener('load', iframeOnLoad, false);
			else
				iframe.attachEvent('onload', iframeOnLoad);

			// Send file
			form.submit();
			form.parentNode.removeChild(form);

			t.files[entryId] = entry;
			t.fileList.appendChild(entry.div);
		},
		cancel: function(el, id) {
			if(!this.files[id])
				return;
			var entry = this.files[id];
			if(entry.status == 1 || !entry.xhr) {
				if(entry.div && entry.div.parentNode)
					entry.div.parentNode.removeChild(entry.div);
				this.files[id] = null;
			}
			if(entry.status == 0)
				entry.xhr.abort();
		},
		traverseFiles: function(files, input) {
			var t = this, c = t.filesUpload.parentNode;
			if (typeof files !== "undefined") {
				for (var i=0, l=files.length; i<l; i++) {
					t.upload(files[i]);
				}
			} else {
				t.uploadFallback(input);
			}
			if(c) {
				try{
					c.removeChild(t.filesUpload);
				}catch(e){}
			}
			t.filesUpload = null;
			t.filesUpload = t.filesUploadTemplate.cloneNode(true);
			c.appendChild(t.filesUpload);
			t.initInput();
		},
		add: function(el) {
			this.filesUpload.click();
			return false;
		}
	};
	window.opload = opload;
})();

/**
 * HikaShop Opload bridge
 * Copyright (c) 2013 Obsidev S.A.R.L.
 */
(function() {
	var hkUploaderList = [];
	window.hkUploaderList = hkUploaderList;

	/**
	 *
	 */
	var hkUploaderMgr = function(id, data) {
		var t = this;
		t.id = id;
		t.url = data.url;
		t.drop = data.drop || (id + '_main');
		t.formData = data.formData || null;
		t.mediaPath = data.mediaPath;
		t.idList = data.idList || (id + '_list');
		t.mode = data.mode || 'single';
		t.imageClickBlocked = false;

		t.options = {
			'maxFilenameSize': 20,
			'truncateBegin': 12,
			'truncateEnd': 6
		};
		if(data.options) {
			for(var o in data.options) {
				if(data.options.hasOwnProperty(o))
					t.options[o] = data.options[o];
			}
		}

		window.hkUploaderList[id] = this;
		t.initUploader();
	};

	/**
	 *
	 */
	hkUploaderMgr.prototype = {
		/**
		 *
		 */
		initUploader: function() {
			var t = this, d = document;
				dest = d.getElementById(t.id);
			if(!dest)
				return false;

			t.oploader = new window.opload(t.id, t.url, t.id, {
				drop:t.drop,
				list:t.idList,
				data:t.formData,
				forceDrop:true,
				maxSize:t.options.maxSize,
				maxFilenameSize: t.options.maxFilenameSize,
				truncateBegin: t.options.truncateBegin,
				truncateEnd: t.options.truncateEnd,
				callbacks:{
					done: function(entry, fallback) {
						var response = fallback;
						if(!response && entry.xhr && entry.xhr.responseText)
							response = entry.xhr.responseText;

						if(!response || response.length == 0 || response == '0')
							return false;

						try	{
							response = window.Oby.evalJSON(response);
						}catch(e) { response = false; }
						if(response == false)
							return false;

						for(var i = 0; i < response.length; i++) {
							if(t.mode == 'single') {
								var r = response[i], dest = d.getElementById(t.id+'_content');
								if(r.html && r.html.length > 0) {
									dest.innerHTML = r.html;
									var empty = d.getElementById(t.id+'_empty');
									if(empty) empty.style.display = 'none';
								}
							} else if(t.mode == 'listImg') {
								var r = response[i];
								if(t.options['imgClasses'] && t.options['imgClasses'][1]) {
									var dest = hkjQuery('#'+t.id+'_content'), myData = document.createElement('li'), className = '';
									className = t.options['imgClasses'][1];
									if(dest.children().length == 0)
										className = t.options['imgClasses'][0];
								}
								if(r.html && r.html.length > 0) {
									hkjQuery(myData).addClass(className).html(r.html).appendTo( dest );
									hkjQuery('#'+t.id+'_empty').hide();
								}
							} else if(t.mode == 'list') {
								var r = response[i];
								if(r.html && r.html.length > 0) {
									var dest = d.getElementById(t.id+'_content'), myData = document.createElement('div');
									hkjQuery(myData).html(r.html).appendTo( dest );
									hkjQuery('#'+t.id+'_empty').hide();
								}
							}
						}
						setTimeout(function(){
							if(entry && entry.id !== undefined)
								window.oploaders[t.id].cancel(null, entry.id);
						}, 1000);
						return true;
					}
				}
			});

			if(t.mode == 'listImg') {
				hkjQuery('#'+t.id+'_content').sortable({
					cursor: "move",
					stop: function(event, ui) {
						var f = hkjQuery('#'+t.id+'_content').children("li").first();
						if(t.options['imgClasses'] && t.options['imgClasses'][1]) {
							if(f.hasClass(t.options['imgClasses'][1])) {
								hkjQuery('#'+t.id+'_content .'+t.options['imgClasses'][0]).removeClass("hikashop_product_main_image_thumb").addClass(t.options['imgClasses'][1]);
								f.removeClass(t.options['imgClasses'][1]).addClass(t.options['imgClasses'][0]);
							}
						}
						t.imageClickBlocked = true; // Firefox trick
						setTimeout(function(){ t.imageClickBlocked = false; }, 150);
					}
				});
				hkjQuery('#'+t.id+'_content').disableSelection();
			}
/*
			hkjQuery(document).bind('drop dragover', function (e) { e.preventDefault(); });
*/
			window.hkUploaderList[t.id].uploadFile = function(el) {
				var dest = document.getElementById(t.id);
				dest.click();
				return false;
			};

			return true;
		},
		/**
		 *
		 */
		uploadFile: function(el) {
			var t = this, h = window.hikashop;
			if(t.uploadFilePopup)
				return t.uploadFilePopup(el);
			h.submitFct = function(data) { t._receiveFile(data); };
			h.openBox(el,null,(el.getAttribute("rel") == null));
			return false;
		},
		/**
		 *
		 */
		browseImage: function(el) {
			var t = this, h = window.hikashop;
			if(t.browseImagePopup)
				return t.browseImagePopup(el);
			h.submitFct = function(data) { t._receiveFile(data); };
			h.openBox(el,null,(el.getAttribute("rel") == null));
			return false;
		},
		/**
		 *
		 */
		_receiveFile: function(data) {
			if(!data || !data.images)
				return;
			var t = this, added = false;
			if(t.mode == 'single') {
				var dest = hkjQuery('#'+t.id+'_content'), r = data.images[0];
				if(r && r.length > 0) {
					dest.html(r);
					added = true;
				}
			} else if(t.mode == 'listImg') {
				var dest = hkjQuery('#'+t.id+'_content'), className = '', r;
				for(var i = 0; i < data.images.length; i++) {
					if(t.options['imgClasses'] && t.options['imgClasses'][1]) {
						className = t.options['imgClasses'][1];
						if(dest.children().length == 0)
							className = t.options['imgClasses'][0];
					}
					r = data.images[i];
					if(r && r.length > 0) {
						var myData = document.createElement('li');
						hkjQuery(myData).addClass(className).html(r).appendTo( dest );
						added = true;
					}
				}
			}
			if(added)
				hkjQuery('#'+t.id+'_empty').hide();
		},
		/**
		 *
		 */
		delImage: function(el, field) {
			var li = el.parentNode, d = document, ul = li.parentNode, empty = d.getElementById(this.id+'_empty');
			if(field === undefined) {
				var child = false;
				window.hikashop.deleteId(li);
				while(ul && !window.Oby.hasClass(ul, 'uploader_data_container'))
					ul = ul.parentNode;
				for(var i = ul.childNodes.length - 1; i >= 0; i--) {
					if(ul.childNodes[i].nodeType == 1) {
						child = true;
						break;
					}
				}
				if(!child && empty) {
					empty.style.display = '';
				}
			} else {
				window.hikashop.deleteId(li);
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = field;
				input.value = '';
				ul.appendChild(input);
				if(empty)
					empty.style.display = '';
			}
			return false;
		},
		delBlock: function(el, field) { return this.delImage(el,field); }
	};
	window.hkUploaderMgr = hkUploaderMgr;
})();
