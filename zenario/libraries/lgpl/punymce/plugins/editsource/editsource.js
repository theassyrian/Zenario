(function(a){a.plugins.EditSource=function(b){var c=a.DOM,f=a.extend,k=a.each,l=a.isWebKit,g=0;b.settings.editsource&&!b.settings.editsource.skip_css||c.loadCSS(a.baseURL+"/plugins/editsource/css/editor.css");b.onBeforeGetContent.add(function(d,b){b.save&&g&&d.setContent(c.get(d.settings.id+"_editsourcearea").value,{load:!0})});f(b.commands,{mceEditSource:function(d,a,e){a=b.getIfr();e=b.settings.id;var f=b.width,m=b.height,h=c.get(e+"_editsource");if(!g)return c.addClass(h,"active"),k(c.select("li",
e+"_c"),function(a){a!=h&&c.addClass(a,"disabled")}),d=c.add(a.parentNode,"textarea",{id:e+"_editsourcearea","class":"editsource",style:"width:"+f+"px;height:"+m+"px;"}),d.value=b.getContent({save:!0}),d.focus(),l||(c.add(a.parentNode,"div",{id:e+"_edspacer","class":"spacer",style:"width:"+f+"px;height:"+m+"px;"}),a.style.display="none"),g=1,!1;g=0;c.removeClass(h,"active");l||(a.style.display="block",d=c.get(e+"_edspacer"),d.parentNode.removeChild(d));d=c.get(e+"_editsourcearea");b.setContent(d.value,
{load:!0});d.parentNode.removeChild(d);k(c.select("li",e+"_c"),function(a){c.removeClass(a,"disabled")});return!1}});f(b.tools,{editsource:{cmd:"mceEditSource",title:a.I18n.editsource}})};a.extend(a.I18n,{editsource:"Edit HTML source"})})(punymce);