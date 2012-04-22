{% spacefull %}
var base_url="{{ globals.base_url }}",
	base_static="{{ globals.base_static }}",
	mobile_client = false,
	base_key, link_id = 0, user_id, user_login;


function redirect(url)
{
	document.location=url;
	return false;
}

function menealo(user, id)
{
	var url = base_url + "backend/menealo.php";
	var content = "id=" + id + "&user=" + user + "&key=" + base_key + "&l=" + link_id + "&u=" + document.referrer;
	url = url + "?" + content;
	disable_vote_link(id, -1, "...", '');
	$.getJSON(url,
		 function(data) {
				parseLinkAnswer(id, data);
		}
	);
	reportAjaxStats('vote', 'link');
}

function menealo_comment(user, id, value)
{
	var url = base_url + "backend/menealo_comment.php";
	var content = "id=" + id + "&user=" + user + "&value=" + value + "&key=" + base_key + "&l=" + link_id ;
	url = url + "?" + content;
	$.getJSON(url,
		 function(data) {
			if (data.error) {
				mDialog.notify("{% trans _('Error:') %} "+data.error, 5);
				return false;
			} else {
				$('#vc-'+id).html(data.votes+"");
				$('#vk-'+id).html(data.karma+"");
				if (data.image.length > 0) {
					$('#c-votes-'+id).html('<img src="'+data.image+'"/>');
				}
			}
		}
	);
	reportAjaxStats('vote', 'comment');
}

function menealo_post(user, id, value)
{
	var url = base_url + "backend/menealo_post.php";
	var content = "id=" + id + "&user=" + user + "&value=" + value + "&key=" + base_key + "&l=" + link_id ;
	url = url + "?" + content;
	$.getJSON(url,
		 function(data) {
			if (data.error) {
				mDialog.notify("{% trans _('Error:') %} "+data.error, 5);
				return false;
			} else {
				$('#vc-'+id).html(data.votes+"");
				$('#vk-'+id).html(data.karma+"");
				if (data.image.length > 0) {
					$('#c-votes-'+id).html('<img src="'+data.image+'"/>');
				}
			}
		}
	);
	reportAjaxStats('vote', 'post');
}

function disable_vote_link(id, value, mess, background) {
	if (value < 0) span = '<span class="negative">';
	else span = '<span>';
	$('#a-va-' + id).html(span+mess+'</span>');
	if (background.length > 0) $('#a-va-' + id).css('background', background);
}

function parseLinkAnswer (id, link) {
	var votes;
	$('#problem-' + id).hide();
	if (link.error || id != link.id) {
		disable_vote_link(id, -1, "{% trans _('grr...') %}", '');
		mDialog.notify("{% trans _('Error:') %} "+link.error, 5);
		return false;
	}
	votes = parseInt(link.votes)+parseInt(link.anonymous);
	if ($('#a-votes-' + link.id).html() != votes) {
		$('#a-votes-' + link.id).hide();
		$('#a-votes-' + link.id).html(votes+"");
		$('#a-votes-' + link.id).fadeIn('slow');
	}
	$('#a-neg-' + link.id).html(link.negatives+"");
	$('#a-usu-' + link.id).html(link.votes+"");
	$('#a-ano-' + link.id).html(link.anonymous+"");
	$('#a-karma-' + link.id).html(link.karma+"");
	disable_vote_link(link.id, link.value, link.vote_description, '');
	return false;
}

function securePasswordCheck(field) {
	if (field.value.length > 5 && field.value.match("^(?=.{6,})(?=(.*[a-z].*))(?=(.*[A-Z0-9].*)).*$", "g")) {
		if (field.value.match("^(?=.{8,})(?=(.*[a-z].*))(?=(.*[A-Z].*))(?=(.*[0-9].*)).*$", "g")) {
			field.style.backgroundColor = "#8FFF00";
		} else {
			field.style.backgroundColor = "#F2ED54";
		}
	} else {
		field.style.backgroundColor = "#F56874";
	}
	return false;
}

function checkEqualFields(field, against) {
	if(field.value == against.value) {
		field.style.backgroundColor = '#8FFF00';
	} else {
		field.style.backgroundColor = "#F56874";
	}
	return false;
}

function enablebutton (button, button2, target)
{
	var string = target.value;
	if (button2 != null) {
		button2.disabled = false;
	}
	if (string.length > 0) {
		button.disabled = false;
	} else {
		button.disabled = true;
	}
}

function checkfield (type, form, field)
{
	var url = base_url + 'backend/checkfield.php?type='+type+'&name=' + encodeURIComponent(field.value);
	$.get(url,
		 function(html) {
			if (html == 'OK') {
				$('#'+type+'checkitvalue').html('<span style="color:black">"' + encodeURI(field.value) + '": ' + html + '</span>');
				form.submit.disabled = '';
			} else {
				$('#'+type+'checkitvalue').html('<span style="color:red">"' + encodeURI(field.value) + '": ' + html + '</span>');
				form.submit.disabled = 'disabled';
			}
		}
	);
	return false;
}

function check_checkfield(fieldname, mess) {
	var field = document.getElementById(fieldname);
	if (field && !field.checked) {
		mDialog.notify(mess, 5);
		// box is not checked
		return false;
	}
}

function report_problem(frm, user, id) {
	if (frm.ratings.value == 0)
		return;
	mDialog.confirm("{% trans _('¿desea votar') %} <em>" + frm.ratings.options[frm.ratings.selectedIndex].text +"</em>?",
		function () {report_problem_yes(frm, user, id)}, function () {report_problem_no(frm, user, id)});
	return false;
}

function report_problem_no(frm, user, id) {
		frm.ratings.selectedIndex=0;
}

function report_problem_yes(frm, user, id) {
	var content = "id=" + id + "&user=" + user + '&value=' +frm.ratings.value + "&key=" + base_key  + "&l=" + link_id + "&u=" + document.referrer;
	var url = base_url + "backend/problem.php?" + content;
	$.getJSON(url,
		 function(data) {
			parseLinkAnswer(id, data);
		}
	);
	reportAjaxStats('vote', 'link');
	return false;
}

// Get voters by Beldar <beldar.cat at gmail dot com>
// Generalized for other uses (gallir at gmail dot com)
function get_votes(program,type,container,page,id) {
	var url = base_url + 'backend/'+program+'?id='+id+'&p='+page+'&type='+type+"&key="+base_key;
	$('#'+container).load(url);
	reportAjaxStats('html', program);
}

// This function report the ajax request to stats events if enabled in your account
// http://code.google.com/intl/es/apis/analytics/docs/eventTrackerOverview.html
function reportAjaxStats(category, action) {
	if (typeof(_gaq) !=	'undefined')
		_gaq.push(['_trackEvent', category, action])
}

function bindTogglePlusMinus(img_id, link_id, container_id) {
	$(document).ready(function (){
		$('#'+link_id).bind('click',
			function() {
				if ($('#'+img_id).attr("src") == plus){
					$('#'+img_id).attr("src", minus);
				}else{
					$('#'+img_id).attr("src", plus);
				}
				$('#'+container_id).slideToggle("fast");
				return false;
			}
		);
	});
}

function clk(f, id) {
	f.href=base_url + 'backend/go.php?id=' + id;
	return true;
}

function fancybox_expand_images(event) {
	if (event.shiftKey) {
		event.preventDefault();
		event.stopImmediatePropagation();

		if(!$('.zoomed').size()) {
			$('body').find('.fancybox[href*=".jpg"] , .fancybox[href*=".gif"] , .fancybox[href*=".png"]').each(
				function() {
					var title=$(this).attr('title');
					var href=$(this).attr('href');
					var img='<div style="margin:10px auto;text-align:center;" class="zoomed"><img style="margin:0 auto;max-width:80%;padding:10px;background:#fff" src="' + href + '"/></div>';
					$(this).after(img);
					$(this).next().click(function(event) { if (event.shiftKey) $('.zoomed').remove(); });
				});
		} else {
			$('.zoomed').remove();
		}
	}
}

function fancybox_gallery(type, user, link) {
	if (! user_id > 0) {
		mDialog.notify('{% trans _('Debe estar autentificado para visualizar imágenes') %}', 5);
		return;
	}
	var url = base_url +'backend/gallery.php?type='+type;
	if (typeof(user) != 'undefined') url = url + '&user=' + user;
	if (typeof(link) != 'undefined') url = url + '&link=' + link;

	if (!$('#gallery').size()) $('body').append('<div id="gallery" style="display:none"></div>');
	$('#gallery').load(url);
}

/**************************************
Tooltips functions
***************************************/
/**
  Strongly modified, onky works with DOM2 compatible browsers.
	Ricardo Galli
  From http://ljouanneau.com/softs/javascript/tooltip.php
 */

(function ($){

	var x = 0;
	var y = 0;
	var offsetx = 7;
	var offsety = 0;
	var reverse = false;
	var top = false;
	var box = null;
	var timer = null;
	var active = false;
	var last = null;
	var ajaxs = {'u': 'get_user_info.php', 'p': "get_post_tooltip.php", 'c': "get_comment_tooltip.php", 'l': "get_link.php"};

	$(start);

	function start() {
		if (box == null) {
			box = $("<div>").attr({ id: 'tooltip-text' });
			$('body').append( box );
		}
		$('a.tooltip, img.tooltip').live('mouseenter mouseleave', function (event) {action(this, event)});
	}

	function action(element, event) {
		if (event.type == 'mouseenter') {
			try {
				args = $(element).attr('class').split(' ')[1].split(':');
				key = args[0];
				value = args[1];
				ajax = ajaxs[key];
				init(event);
				timer = setTimeout(function() {ajax_request(event, ajax, value)}, 250);
			}
			catch (e) {
				hide();
				return false;
			}
		} else if (event.type == 'mouseleave') {
			hide();
		}
		return false;
	}

	function init(event) {
		if (timer || active) hide();
		active = true;

		$(document).bind('mousemove.tooltip', function (e) { mouseMove(e) });
		if (box.outerWidth() > 0) {
			if ($(window).width() - event.pageX < box.outerWidth() * 1.05) reverse = true;
			else reverse = false;
			if ($(window).height() - (event.pageY - $(window).scrollTop()) < 200) top = true;
			else top = false;
		}
	}

	function show(html) {
		if (active) {
			if(typeof html == 'string')	box.html(html);
			position();
			box.show();
		}
	}

	function hide () {
		if (timer != null) {
			clearTimeout(timer);
			timer = null;
		}
		$(document).unbind('mousemove.tooltip');
		active = false;
		box.hide();
	}

	function position() {
		if (reverse) xL = x - (box.outerWidth() + offsetx);
		else xL = x + offsetx;
		if (top) yL = y - (box.outerHeight() + offsety);
		else yL = y + offsety;
		box.css({left: xL +"px", top: yL +"px"});
	}

	function mouseMove(e) {
		x = e.pageX;
		y = e.pageY;
		position();
	}

	function ajax_request(event, script, id) {
		timer = null;
		var url = base_url + 'backend/'+script+'?id='+id;
		if (url == last) {
			show();
		} else {
			show('');
			$.ajax({
				url: url,
				dataType: "html",
				success: function(html) {
					last = url;
					show(html)
					reportAjaxStats('tooltip', script);
				}
			});
		}
	}
})(jQuery)
/**
 *  Based on jqDialog from:
 *
	Kailash Nadh, http://plugins.jquery.com/project/jqDialog
**/

function strip_tags(html) {
	return html.replace(/<\/?[^>]+>/gi, '');
}

var mDialog = new function() {
	this.closeTimer = null;
	this.divBox = null;


	this.std_alert = function(message, callback) {
		alert(strip_tags(message));
		if (callback) callback();
	}

	this.std_confirm = function(message, callback_yes, callback_no) {
		if (confirm(strip_tags(message))) {
			if (callback_yes) callback_yes();
		} else {
			if (callback_no) callback_no();
		}
	}

	this.std_prompt = function(message, content, callback_ok, callback_cancel) {
		var res = prompt(message, content);
		if (res != null) {
			if (callback_ok) callback_ok(res);
		} else {
			if (callback_cancel) callback_cancel(res);
		}
	}

	//________create a confirm box
	this.confirm = function(message, callback_yes, callback_no) {
		if (mobile_client) {
			this.std_confirm(message, callback_yes, callback_no);
			return;
		}
		this.createDialog(message);
		this.btYes.show(); this.btNo.show();
		this.btOk.hide(); this.btCancel.hide(); this.btClose.hide();
		this.btYes.focus();

		// just redo this everytime in case a new callback is presented
		this.btYes.unbind().click( function() {
			mDialog.close();
			if(callback_yes) callback_yes();
		});

		this.btNo.unbind().click( function() {
			mDialog.close();
			if(callback_no) callback_no();
		});
	};

	//________prompt dialog
	this.prompt = function(message, content, callback_ok, callback_cancel) {
		if (mobile_client) {
			this.std_prompt(message, content, callback_ok, callback_cancel);
			return;
		}

		this.createDialog($("<p>").append(message).append( $("<p>").append( $(this.input).val(content) ) ));

		this.btYes.hide(); this.btNo.hide();
		this.btOk.show(); this.btCancel.show();
		this.input.focus();

		// just redo this everytime in case a new callback is presented
		this.btOk.unbind().click( function() {
			mDialog.close();
			if(callback_ok) callback_ok(mDialog.input.val());
		});

		this.btCancel.unbind().click( function() {
			mDialog.close();
			if(callback_cancel) callback_cancel();
		});
	};

	//________create an alert box
	this.alert = function(content, callback_ok) {
		if (mobile_client) {
			this.std_alert(content, callback_ok);
			return;
		}
		this.createDialog(content);
		this.btCancel.hide(); this.btYes.hide(); this.btNo.hide();
		this.btOk.show();
		this.btOk.focus();

		// just redo this everytime in case a new callback is presented
		this.btOk.unbind().click( function() {
			mDialog.close();
			if(callback_ok) callback_ok();
		});
	};


	//________create a dialog with custom content
	this.content = function(content, close_seconds) {
		if (mobile_client) {
			this.std_alert(content, false);
			return;
		}
		this.createDialog(content);
		this.divOptions.hide();
	};

	//________create an auto-hiding notification
	this.notify = function(content, close_seconds) {
		if (mobile_client) {
			this.std_alert(content, false);
			return;
		}
		this.content(content);
		this.btClose.focus();
		if(close_seconds)
			this.closeTimer = setTimeout(function() { mDialog.close(); }, close_seconds*1000 );
	};

	//________dialog control
	this.createDialog = function(content) {
		if (this.divBox == null) this.init();
		clearTimeout(this.closeTimer);
		this.divOptions.show();
		this.divContent.html(content);
		this.divBox.fadeIn('fast');
		this.maintainPosition();
	};

	this.close = function() {
		this.divBox.fadeOut('fast');
		$(window).unbind('scroll.mDialog');
	};

	this.makeCenter = function() {
		$(mDialog.divBox).css (
			{
				top: ( (($(window).height() / 2) - ( mDialog.h / 2 ) )) + ($(document).scrollTop()) + 'px',
				left: ( (($(window).width() / 2) - ( mDialog.w / 2 ) )) + ($(document).scrollLeft()) + 'px'
			}
		);
	};
	this.maintainPosition = function() {
		mDialog.w = mDialog.divBox.width();
		mDialog.h = mDialog.divBox.height();
		mDialog.makeCenter();
		$(window).bind('scroll.mDialog', function() {
			mDialog.makeCenter();
		} );

	}

	//________
	this.init = function() {
		if (mobile_client) return;
		this.divBox = $("<div>").attr({ id: 'mDialog_box' });
		this.divHeader = $("<div>").attr({ id: 'mDialog_header' });
		this.divContent = $("<div>").attr({ id: 'mDialog_content' });
		this.divOptions = $("<div>").attr({ id: 'mDialog_options' });
		this.btYes = $("<button>").attr({ id: 'mDialog_yes' }).text("{% trans _('Sí') %}");
		this.btNo = $("<button>").attr({ id: 'mDialog_no' }).text("{% trans _('No') %}");
		this.btOk = $("<button>").attr({ id: 'mDialog_ok' }).text("{% trans _('Vale') %}");
		this.btCancel = $("<button>").attr({ id: 'mDialog_ok' }).text("{% trans _('Cancelar') %}");
		this.input = $("<input>").attr({ id: 'mDialog_input' });
		this.btClose = $("<span>").attr({ id: 'mDialog_close' }).text('X').click(
							function() {
								mDialog.close();
							});
		this.divHeader.append(  this.btClose );
		this.divBox.append(this.divHeader).append( this.divContent ).append(
			this.divOptions.append(this.btNo).append(this.btCancel).append(this.btOk).append(this.btYes)
		);

		this.divBox.hide();
		$('body').append( this.divBox );
	};

};

function comment_reply(id) {
	var ref = '#' + id + ' ';
	var textarea = $('#comment');
	if (textarea.length == 0 ) return;
	var re = new RegExp(ref);
	var oldtext = textarea.val();
	if (oldtext.match(re)) return;
	if (oldtext.length > 0 && oldtext.charAt(oldtext.length-1) != "\n") oldtext = oldtext + "\n";
	textarea.val(oldtext + ref);
	textarea.get(0).focus();
}

function post_load_form(id, container) {
	var url = base_url + 'backend/post_edit.php?id='+id+"&key="+base_key;
	$.get(url, function (html) {
			if (html.length > 0) {
				if (html.match(/^ERROR:/i)) {
					mDialog.notify(html, 2);
				} else {
					$('#'+container).html(html);
				}
				reportAjaxStats('html', 'post_edit');
			}
		});
}


function post_new() {
	post_load_form(0, 'addpost');
}

function post_edit(id) {
	post_load_form(id, 'pcontainer-'+id);
}

function post_reply(id, user) {
	var ref = '@' + user + ',' + id + ' ';
	var others = '';
	var regex = /get_post_url.php\?id=([a-z0-9%_\.\-]+(\,\d+){0,1})/ig;
	var text = $('#pid-'+id).html();
	var startSelection, endSelection, textarea;

	while (a = regex.exec(text)) { // Add references to others
		u = decodeURIComponent(a[1]);
		if ( ! u.match('^'+user_login)) { // exclude references to the reader
			others = others + '@' + u + ' ';
		}
	}
	if (others.length > 0) {
		startSelection = ref.length;
		endSelection = startSelection + others.length;
		ref = ref + others;
	} else {
		startSelection = endSelection = 0;
	}
	textarea = $('#post');
	if (textarea.length == 0) {
		post_new();
	}
	post_add_form_text(ref, 1, startSelection, endSelection);
}

function post_add_form_text(text, tries, start, end) {
	if (! tries) tries = 1;
	var textarea = $('#post');
	if (tries < 20 && textarea.length == 0) {
			tries++;
			setTimeout(function () { post_add_form_text(text,tries,start,end) }, 50);
			return false;
	}
	if (textarea.length == 0 ) return false;
	var re = new RegExp(text);
	var oldtext = textarea.val();
	if (oldtext.match(re)) return false;
	var offset = oldtext.length;
	if (oldtext.length > 0 && oldtext.charAt(oldtext.length-1) != ' ') {
		oldtext = oldtext + ' ';
		offset = offset + 1;
	}
	textarea.val(oldtext + text);
	var obj = textarea[0];
	obj.focus();
	if ('selectionStart' in obj && start > 0 && end > 0) {
		obj.selectionStart = start + offset;
		obj.selectionEnd = end + offset;
	}
}

// See http://www.shiningstar.net/articles/articles/javascript/dynamictextareacounter.asp?ID=AW
var textCounter = function (field,cntfield,maxlimit) {
	if (textCounter.timer) return;
	textCounter.timer = setTimeout( function () {
		textCounter.timer = false;
		var length = field.value.length;
		if (length > maxlimit) {
			field.value = field.value.substring(0, maxlimit);
			length = maxlimit;
		}
		if (textCounter.length != length) {
			cntfield.value = maxlimit - length;
			textCounter.length = length;
		}
	}, 300);
};
textCounter.timer = false;
textCounter.length = 0;

/************************
Simple format functions
**********************************/
/*
  Code from http://www.gamedev.net/community/forums/topic.asp?topic_id=400585
  strongly improved by Juan Pedro López for http://meneame.net
  2006/10/01, jotape @ http://jplopez.net
*/

function applyTag(id, tag) {
	var obj = document.getElementById(id);
	if (obj) wrapText(obj, tag, tag);
	return false;
}

function wrapText(obj, tag) {
	if(typeof obj.selectionStart == 'number') {
		// Mozilla, Opera and any other true browser
		var start = obj.selectionStart;
		var end   = obj.selectionEnd;

		if (start == end || end < start) return false;
		obj.value = obj.value.substring(0, start) +  replaceText(obj.value.substring(start, end), tag) + obj.value.substring(end, obj.value.length);
	} else if(document.selection) {
		// Damn Explorer
		// Checking we are processing textarea value
		obj.focus();
		var range = document.selection.createRange();
		if(range.parentElement() != obj) return false;
		if (range.text == "") return false;
		if(typeof range.text == 'string')
			document.selection.createRange().text =  replaceText(range.text, tag);
	} else
		obj.value += text;
}

function replaceText(text, tag) {
		return '<'+tag+'>'+text+'</'+tag+'>';
}

/* Privates */
function priv_show(content) {
	$.fancybox({content: content, autoDimensions: false, width: 500, height: 'auto', transitionIn: 'none'});
}

function priv_new(user_id) {
	var url = base_url + 'backend/priv_edit.php?user_id='+user_id+"&key="+base_key;
	$.fancybox({href: url,
		onComplete: function () { if (user_id > 0) $('#post').focus(); else $("#to_user").focus();},
		hideOnOverlayClick: false,
		titleShow: false,
		centerOnScroll: false,
		scrolling: 'no',
		autoDimensions: false,
		width: 500,
		height: 'auto'});
}

/* Answers */
function get_total_answers_by_ids(type, ids) {
	$.ajax({
		type: 'POST',
		url: base_url + 'backend/get_total_answers.php',
		dataType: 'json',
		data: { "ids": ids, "type": type },
		success: function (data) { $.each(data, function (ids, answers) { show_total_answers(type, ids, answers) } ) }
	});
	reportAjaxStats('json', 'total_answers_ids');
}

function get_total_answers(type, order, id, offset, size) {
	$.getJSON(base_url + 'backend/get_total_answers.php', { "id": id, "type": type, "offset": offset, "size": size, "order": order },
		function (data) { $.each(data, function (ids, answers) { show_total_answers(type, ids, answers) } ) });
	reportAjaxStats('json', 'total_answers');
}

function show_total_answers(type, id, answers) {
	if (type == 'comment') dom_id = '#cid-'+ id;
	else dom_id = '#pid-'+ id;
	$(dom_id).siblings(".comment-meta").children(".comment-votes-info").append('<a href="javascript:show_answers(\''+type+'\','+id+')" title="'+answers+' {% trans _('respuestas') %}"><img style="margin-right:0px" src="{{ globals.base_static }}img/common/replies-01.png" width="16" height="14"/><span class="counter answers">'+answers+'</span></a>');
}

function show_answers(type, id) {
	var program, dom_id, answers;

	if (type == 'comment') {
		program = 'get_comment_answers.php';
		dom_id = '#cid-'+ id;
	} else {
		program = 'get_post_answers.php';
		dom_id = '#pid-'+ id;
	}
	answers = $('#answers-'+id);
	if (answers.length == 0) {
		$.get(base_url + 'backend/'+program, { "type": type, "id": id }, function (html) {
			$(dom_id).parent().parent().append('<div class="comment-answers" id="answers-'+id+'">'+html+'</div>');
		});
		reportAjaxStats('html', program);
	} else {
		answers.toggle();
	}
}

$(document).ready(function () {
	var m, m2, target, canonical;

	if ((m = location.href.match(/#([\w\-]+)$/))) {
		target = $('#'+m[1]);
		{# Highlight a comment if it is referenced by the URL. Currently double border, width must be 3 at least #}
		if (link_id > 0 && (m2 = m[1].match(/^c-(\d+)$/)) && m2[1] > 0) {
			if ( target.length > 0) {
				$("#"+m[1]+">:first").css("border-style","solid").css("border-width","1px");
			} else {
				/* It's a link to a comment, check it exists, otherwise redirect to the right page */
				canonical = $("link[rel^='canonical']");
				if (canonical.length > 0) {
					self.location = canonical.attr("href") + "/000" + m2[1];
					return;
				}
			}
		} else {
			target.hide();
			target.fadeIn(1000);
		}

		{# If there is an anchor in the url, displace 80 pixels down due to the fixed header #}
		if ($('#headerwrap').css('position') == 'fixed') {
			var scroll = $(window).scrollTop();
			if (scroll > 80) $(window).scrollTop(scroll-80);
		}
	}
	mDialog.init();

});

// Drop an image file
// Modified from http://gokercebeci.com/dev/droparea
(function( $ ){
	var s;
	// Methods
	var m = {
		init: function(e){},
		start: function(e){},
		complete: function(r){},
		error: function(r){ alert(r.error); return false; },
		traverse: function(files, area) {

			var form = area.parents('form');
			form.find('input[name="tmp_filename"], input[name="tmp_filetype"]').remove();

			if (typeof files !== "undefined") {
				if (m.check_files(files, area)) {
					for (var i=0, l=files.length; i<l; i++) {
						m.upload(files[i], area);
					}
				}
			} else {
				mDialog.notify("{% trans _('formato no reconocido') %}", 5);
			}
		},

		check_files: function(files, area) {
			if (files != undefined) {
				for (var i = 0; i < files.length; i++) {
					// File type control
					if (typeof FileReader === "undefined" || !(/image/i).test(files[i].type)) {
						mDialog.notify("{% trans _('sólo se admiten imágenes') %}", 5);
						return false;
					}
					if (files[i].fileSize > s.maxsize) {
						mDialog.notify("{% trans _('tamaño máximo excedido') %}" + ":<br/>" + files[i].fileSize + " > " + s.maxsize + " bytes", 5);
						return false;
					}
				}
				return true;
			}
		},

		upload: function(file, area) {
			var form = area.parents('form');
			var progress = form.find('progress').show();
			var thumb = form.find('.droparea_info img');

			progress.attr('max', file.fileSize).attr('value', 0);

			var xhr = new XMLHttpRequest();

			// Update progress bar
			xhr.upload.addEventListener("progress", function (e) {
				if (e.lengthComputable) {
					progress.attr("value", e.loaded);
					//var loaded = Math.ceil((e.loaded / e.total) * 100) + "%";
				}
			}, false);

			// File uploaded
			xhr.addEventListener("load", function (e) {
				var r = jQuery.parseJSON(e.target.responseText);
				if (typeof r.error === 'undefined') {
					thumb.attr('src', r.thumb).show();
					progress.attr("value", file.fileSize);
					form.find('input[name="tmp_filename"], input[name="tmp_filetype"]').remove();
					form.append('<input type="hidden" name="tmp_filename" value="'+r.name+'"/>');
					form.append('<input type="hidden" name="tmp_filetype" value="'+r.type+'"/>');
					s.complete(r);
				} else {
					s.error(r);
				}
				setTimeout(function () {progress.hide();}, s.hide_delay);
			}, false);

			xhr.open("post", s.post, true);

			// Set appropriate headers
			xhr.setRequestHeader("Content-Type", "multipart/form-data-alternate");
			xhr.setRequestHeader("X-File-Name", file.fileName);
			xhr.setRequestHeader("X-File-Size", file.fileSize);
			xhr.setRequestHeader("X-File-Type", file.type);

			xhr.send(file);
		}
	};
	$.fn.droparea = function(o) {
		// Check support for HTML5 File API
		if (!window.File) return;

		// Settings
		s = {
			'post': base_url + 'backend/tmp_upload.php',
			'init': m.init,
			'start': m.start,
			'complete': m.complete,
			'error': m.error,
			'maxsize': 500000, // Bytes
			'show_thumb': true,
			'hide_delay': 2000,
			'backgroundColor': '#AFFBBB',
			'backgroundImage': base_static +'img/common/picture_simple01.png'
		};

		this.each(function(){
			if(o) $.extend(s, o);
			var form = $(this);

			s.init(form);

			form.find('input[type="file"]').change(function () {
				m.traverse(this.files, $(this))
				$(this).val("");
			});

			if (s.show_thumb) {
				var thumb = $('<img width="40" height="40" style="float:right;"/>').hide();
				form.find('.droparea_info').append(thumb);
			}

			var progress = $('<progress value="0" max="0" style="float:right;margin-right:4px;"></progress>').hide();
			form.find('.droparea_info').append(progress);

			form.find('.droparea')
			.bind({
				dragleave: function (e) {
					var area = $(this);
					e.preventDefault();
					area.css(area.data('bg'));
				},

				dragenter: function (e) {
					e.preventDefault();
					$(this).css({
						'background-color': s.backgroundColor,
						'background-image': 'url("'+s.backgroundImage+'")',
						'background-position': 'center',
						'background-repeat': 'no-repeat'
						});

				},

				dragover: function (e) {
					e.preventDefault();
				}
			})
			.each(function() {
				var bg;
				var area = $(this);

				bg = {
					'background-color': area.css('background-color'),
					'background-image': area.css('background-image'),
					'background-position': area.css('background-position')
				}
				area.data("bg", bg);
				this.addEventListener("drop", function (e) {
					e.preventDefault();
					s.start(area);
					m.traverse(e.dataTransfer.files, area);
					area.css(area.data('bg'));
				},false);
			});
		});
	};
})( jQuery );


{% endspacefull %}
