(function(t){t.plugins.ForceBlocks=function(h){function H(a){var c;c=a.document;c="CSS1Compat"==c.compatMode?c.documentElement:c.body;return{x:a.pageXOffset||c.scrollLeft,y:a.pageYOffset||c.scrollTop,w:a.innerWidth||c.clientWidth,h:a.innerHeight||c.clientHeight}}function y(a,c,g){for(var f=h.getDoc().createTreeWalker(a,4,null,!1),b=-1;a=f.nextNode();){b++;if(0==c&&a==g)return b;if(1==c&&b==g)return a}return-1}function A(){var a=h.getBody(),c=h.getDoc(),g=h.selection,f=g.getSel(),b=g.getRng(),e=-2,
l,p,r,m,d,k=a.childNodes,s,q,n;for(s=k.length-1;0<=s;s--)if(d=k[s],3==d.nodeType||!w(d)&&8!=d.nodeType)if(m)m.hasChildNodes()?m.insertBefore(d,m.firstChild):m.appendChild(d);else{if(3!=d.nodeType||/[^\s]/g.test(d.nodeValue))-2==e&&b&&(x?(m=c.body.createTextRange(),m.moveToElementText(a),m.collapse(1),e=-1*m.move("character",-16777215),m=b.duplicate(),m.collapse(1),l=-1*m.move("character",-16777215),m=b.duplicate(),m.collapse(0),m=-1*m.move("character",-16777215)-l,e=l-e,l=m):1==b.startContainer.nodeType&&
(q=b.startContainer.childNodes[b.startOffset])&&1==q.nodeType?(n=q.getAttribute("id"),q.setAttribute("id","__mce")):h.dom.getParent(b.startContainer,function(b){return b===a})&&(p=b.startOffset,r=b.endOffset,e=y(a,0,b.startContainer),l=y(a,0,b.endContainer))),m=h.dom.create(u.element),m.appendChild(d.cloneNode(1)),d.parentNode.replaceChild(m,d)}else m=null;if(-2!=e)if(x)try{b=f.createRange(),b.moveToElementText(a),b.collapse(1),b.moveStart("character",e),b.moveEnd("character",l),b.select()}catch(t){}else m=
a.getElementsByTagName(u.element)[0],b=c.createRange(),-1!=e?b.setStart(y(a,1,e),p):b.setStart(m,0),-1!=l?b.setEnd(y(a,1,l),r):b.setEnd(m,0),f&&(f.removeAllRanges(),f.addRange(b));else!x&&(q=h.dom.get("__mce"))&&(n?q.setAttribute("id",n):q.removeAttribute("id"),b=c.createRange(),b.setStartBefore(q),b.setEndBefore(q),g.setRng(b))}function w(a){return 1==a.nodeType&&/^(H[1-6]|P|DIV|ADDRESS|PRE|FORM|TABLE|LI|OL|UL|TD|CODE|CAPTION|BLOCKQUOTE|CENTER|DL|DT|DD|DIR|FIELDSET|NOSCRIPT|NOFRAMES|MENU|ISINDEX|SAMP)$/.test(a.nodeName)}
function B(a){return C.getParent(a,function(a){return w(a)})}function D(a){a=a.innerHTML;a=a.replace(/<img|hr|table/g,"d");a=a.replace(/<[^>]+>/g,"");return""==a.replace(/[ \t\r\n]+/g,"")}function I(a){a=h.getDoc();var c=h.selection.getSel(),g=h.selection.getRng(),g=a.body,f,b,e,l,p,r,m,d,k,s,q,n;if(t.isOldWebKit)return!0;f=a.createRange();f.setStart(c.anchorNode,c.anchorOffset);f.collapse(!0);b=a.createRange();b.setStart(c.focusNode,c.focusOffset);b.collapse(!0);l=(e=0>f.compareBoundaryPoints(f.START_TO_END,
b))?c.anchorNode:c.focusNode;p=e?c.anchorOffset:c.focusOffset;r=e?c.focusNode:c.anchorNode;m=e?c.focusOffset:c.anchorOffset;l="BODY"==l.nodeName?l.firstChild:l;r="BODY"==r.nodeName?r.firstChild:r;d=B(l);k=B(r);e=d?d.nodeName:u.element;if(C.getParent(d,function(a){return/OL|UL/.test(a.nodeName)}))return!0;d&&("CAPTION"==d.nodeName||/absolute|relative|static/gi.test(d.style.position))&&(e=u.element,d=null);k&&("CAPTION"==k.nodeName||/absolute|relative|static/gi.test(k.style.position))&&(e=u.element,
k=null);if(/(TD|TABLE|TH|CAPTION)/.test(e)||d&&"DIV"==e&&/left|right/gi.test(d.style.cssFloat))e=u.element,d=k=null;d=d&&d.nodeName==e?d.cloneNode(0):a.createElement(e);k=k&&k.nodeName==e?k.cloneNode(0):a.createElement(e);k.id="";/^(H[1-6])$/.test(e)&&l.nodeValue&&p==l.nodeValue.length&&(k=a.createElement(u.element));n=s=l;do{if(n==g||9==n.nodeType||w(n)||/(TD|TABLE|TH|CAPTION)/.test(n.nodeName))break;s=n}while(n=n.previousSibling?n.previousSibling:n.parentNode);n=q=r;do{if(n==g||9==n.nodeType||w(n)||
/(TD|TABLE|TH|CAPTION)/.test(n.nodeName))break;q=n}while(n=n.nextSibling?n.nextSibling:n.parentNode);s.nodeName==e?f.setStart(s,0):f.setStartBefore(s);f.setEnd(l,p);d.appendChild(f.cloneContents());b.setEndAfter(q);b.setStart(r,m);k.appendChild(b.cloneContents());g=a.createRange();s.previousSibling||s.parentNode.nodeName!=e?f.startContainer.nodeName==e&&0==f.startOffset?g.setStartBefore(f.startContainer):g.setStart(f.startContainer,f.startOffset):g.setStartBefore(s.parentNode);q.nextSibling||q.parentNode.nodeName!=
e?g.setEnd(b.endContainer,b.endOffset):g.setEndAfter(q.parentNode);g.deleteContents();d.firstChild&&d.firstChild.nodeName==e&&(d.innerHTML=d.firstChild.innerHTML);k.firstChild&&k.firstChild.nodeName==e&&(k.innerHTML=k.firstChild.innerHTML);D(d)&&(d.innerHTML=z?" <br />":"<br />");D(k)&&(k.innerHTML=z?" <br />":"<br />");z?(g.insertNode(d),g.insertNode(k)):(g.insertNode(k),g.insertNode(d));k.normalize();d.normalize();g=a.createRange();g.selectNodeContents(k);g.collapse(1);c.removeAllRanges();c.addRange(g);
vp=H(h.getWin());a=h.dom.getPos(k).y;c=k.clientHeight;(a<vp.y||a+c>vp.y+vp.h)&&h.getWin().scrollTo(0,a<vp.y?a:a-vp.h+25);return!1}function J(a,c){function g(a){var c;(a=a.target)&&a.parentNode&&"BR"==a.nodeName&&(b=B(a))&&(c=a.previousSibling,v.remove(f,"DOMNodeInserted",g),c&&3==c.nodeType&&/\s+$/.test(c.nodeValue)||(a.previousSibling||a.nextSibling)&&a.parentNode.removeChild(a))}var f=h.getBody(),b,e=h.selection,l=e.getRng(),p=l.startContainer,r;if(p&&w(p)&&!/^(TD|TH)$/.test(p.nodeName)&&c&&(0==
p.childNodes.length||1==p.childNodes.length&&"BR"==p.firstChild.nodeName)){for(b=p;(b=b.previousSibling)&&!w(b););if(b){if(p!=f.firstChild){for(l=h.getDoc().createTreeWalker(b,NodeFilter.SHOW_TEXT,null,!1);r=l.nextNode();)b=r;l=h.getDoc().createRange();l.setStart(b,b.nodeValue?b.nodeValue.length:0);l.setEnd(b,b.nodeValue?b.nodeValue.length:0);e.getSel().removeAllRanges();e.getSel().addRange(l);p.parentNode.removeChild(p)}return v.cancel(a)}}v._add(f,"DOMNodeInserted",g);window.setTimeout(function(){v._remove(f,
"DOMNodeInserted",g)},1)}var v=t.Event,C,x,E,z,F,G,u;x=t.isIE;E=t.isGecko;z=t.isOpera;F=t.each;G=t.extend;this.settings=u=G({element:"P"},h.settings.forceblocks);h.onPreInit.add(function(){C=h.dom;v.add(h.getDoc(),"keyup",A);h.onSetContent.add(A);h.onBeforeGetContent.add(A);x||(h.onPreProcess.add(function(a,c){F(c.node.getElementsByTagName("br"),function(c){var f=c.parentNode;!f||"p"!=f.nodeName||1!=f.childNodes.length&&f.lastChild!=c||f.replaceChild(a.getDoc().createTextNode("\u00a0"),c)})}),v.add(h.getDoc(),
"keypress",function(a){if(13==a.keyCode&&!a.shiftKey&&!I(a))return v.cancel(a)}),E&&v.add(h.getDoc(),"keydown",function(a){8!=a.keyCode&&46!=a.keyCode||a.shiftKey||J(a,8==a.keyCode)}))},this);x||h.onSetContent.add(function(a,c){"html"==c.format&&(c.content=c.content.replace(/<p>[\s\u00a0]+<\/p>/g,"<p><br /></p>"))});h.onPostProcess.add(function(a,c){c.content=c.content.replace(/<p><\/p>/g,"<p>\u00a0</p>")})}})(punymce);