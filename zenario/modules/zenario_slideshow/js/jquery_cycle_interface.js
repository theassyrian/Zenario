/*
 * Copyright (c) 2018, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
(function(zenario, zenario_cycle_interface, undefined) {

var methods = methodsOf(zenario_cycle_interface);


methods.show = function(containerId, opt, startingSlide) {
	$('#' + containerId + ' .nest_plugins_wrap').cycle({
		fx: opt.fx, sync: opt.sync, timeout: opt.timeout, speed: opt.speed, pause: opt.pause,
		nowrap: !opt.next_prev_buttons_loop,
		startingSlide:startingSlide,
		before: function(currSlideElement, nextSlideElement, options, forwardFlag) {
			if (options.addSlide) {
				var tab = options.nextSlide + 1,
					sel = '#' + containerId + ' .tab_' + tab;
				
				$('#' + containerId + ' .tab_on').not(sel).removeClass('tab_on').addClass('tab');
				$(sel).removeClass('tab').addClass('tab_on');
			}
		}
		
	});
}

methods.page = function(containerId, i, mouseover) {
	$('#' + containerId + ' .nest_plugins_wrap').cycle(i);
	
	if (mouseover) {
		this.pause(containerId);
	}
	
	return false;
}

methods.next = function(containerId) {
	$('#' + containerId + ' .nest_plugins_wrap').cycle('next');
	return false;
}

methods.prev = function(containerId) {
	$('#' + containerId + ' .nest_plugins_wrap').cycle('prev');
	return false;
}

methods.pause = function(containerId) {
	$('#' + containerId + ' .nest_plugins_wrap').cycle('pause');
}

methods.resume = function(containerId) {
	$('#' + containerId + ' .nest_plugins_wrap').cycle('resume');
}



})(zenario, window.zenario_cycle_interface = function() {});