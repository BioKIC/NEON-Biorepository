(globalThis.TURBOPACK||(globalThis.TURBOPACK=[])).push(["object"==typeof document?document.currentScript:void 0,29735,(e,t,a)=>{var n,i;(i=n||(n={})).Root="root",i.Text="text",i.Directive="directive",i.Comment="comment",i.Script="script",i.Style="style",i.Tag="tag",i.CDATA="cdata",i.Doctype="doctype",n.Root,n.Text,n.Directive,n.Comment,n.Script,n.Style,n.Tag,n.CDATA,n.Doctype,Object.defineProperty(a,"ElementType",{enumerable:!0,get:function(){return n}}),a.isTag=function(e){return e.type===n.Tag||e.type===n.Script||e.type===n.Style}},86863,(e,t,a)=>{let n=e.r(29735);var i=class{parent=null;prev=null;next=null;startIndex=null;endIndex=null;get parentNode(){return this.parent}set parentNode(e){this.parent=e}get previousSibling(){return this.prev}set previousSibling(e){this.prev=e}get nextSibling(){return this.next}set nextSibling(e){this.next=e}cloneNode(e=!1){return x(this,e)}},r=class extends i{data;constructor(e){super(),this.data=e}get nodeValue(){return this.data}set nodeValue(e){this.data=e}},s=class extends r{type=n.ElementType.Text;get nodeType(){return 3}},l=class extends r{type=n.ElementType.Comment;get nodeType(){return 8}},o=class extends r{type=n.ElementType.Directive;name;constructor(e,t){super(t),this.name=e}get nodeType(){return 1}"x-name";"x-publicId";"x-systemId"},d=class extends i{children;constructor(e){super(),this.children=e}get firstChild(){return this.children[0]??null}get lastChild(){return this.children.length>0?this.children[this.children.length-1]:null}get childNodes(){return this.children}set childNodes(e){this.children=e}},c=class extends d{type=n.ElementType.CDATA;get nodeType(){return 4}},u=class extends d{type=n.ElementType.Root;get nodeType(){return 9}},p=class extends d{name;attribs;type;constructor(e,t,a=[],i="script"===e?n.ElementType.Script:"style"===e?n.ElementType.Style:n.ElementType.Tag){super(a),this.name=e,this.attribs=t,this.type=i}get nodeType(){return 1}get tagName(){return this.name}set tagName(e){this.name=e}get attributes(){return Object.keys(this.attribs).map(e=>({name:e,value:this.attribs[e],namespace:this["x-attribsNamespace"]?.[e],prefix:this["x-attribsPrefix"]?.[e]}))}namespace;"x-attribsNamespace";"x-attribsPrefix"};function h(e){return n.isTag(e)}function m(e){return e.type===n.ElementType.CDATA}function f(e){return e.type===n.ElementType.Text}function g(e){return e.type===n.ElementType.Comment}function b(e){return e.type===n.ElementType.Directive}function w(e){return e.type===n.ElementType.Root}function x(e,t=!1){let a;if(f(e))a=new s(e.data);else if(g(e))a=new l(e.data);else if(h(e)){let n=t?v(e.children):[],i=new p(e.name,{...e.attribs},n);for(let e of n)e.parent=i;null!=e.namespace&&(i.namespace=e.namespace),e["x-attribsNamespace"]&&(i["x-attribsNamespace"]={...e["x-attribsNamespace"]}),e["x-attribsPrefix"]&&(i["x-attribsPrefix"]={...e["x-attribsPrefix"]}),a=i}else if(m(e)){let n=t?v(e.children):[],i=new c(n);for(let e of n)e.parent=i;a=i}else if(w(e)){let n=t?v(e.children):[],i=new u(n);for(let e of n)e.parent=i;e["x-mode"]&&(i["x-mode"]=e["x-mode"]),a=i}else if(b(e)){let t=new o(e.name,e.data);null!=e["x-name"]&&(t["x-name"]=e["x-name"],t["x-publicId"]=e["x-publicId"],t["x-systemId"]=e["x-systemId"]),a=t}else throw Error(`Not implemented yet: ${e.type}`);return a.startIndex=e.startIndex,a.endIndex=e.endIndex,null!=e.sourceCodeLocation&&(a.sourceCodeLocation=e.sourceCodeLocation),a}function v(e){let t=e.map(e=>x(e,!0));for(let e=1;e<t.length;e++)t[e].prev=t[e-1],t[e-1].next=t[e];return t}a.CDATA=c,a.Comment=l,a.DataNode=r,a.Document=u,a.Element=p,a.Node=i,a.NodeWithChildren=d,a.ProcessingInstruction=o,a.Text=s,a.cloneNode=x,a.isCDATA=m,a.isComment=g,a.isDirective=b,a.isDocument=w,a.isTag=h,a.isText=f},99235,(e,t,a)=>{let n=["animateMotion","animateTransform","clipPath","feBlend","feColorMatrix","feComponentTransfer","feComposite","feConvolveMatrix","feDiffuseLighting","feDisplacementMap","feDropShadow","feFlood","feFuncA","feFuncB","feFuncG","feFuncR","feGaussianBlur","feImage","feMerge","feMergeNode","feMorphology","feOffset","fePointLight","feSpecularLighting","feSpotLight","feTile","feTurbulence","foreignObject","linearGradient","radialGradient","textPath"],i=n.reduce((e,t)=>(e[t.toLowerCase()]=t,e),{});a.CASE_SENSITIVE_TAG_NAMES=n,a.CASE_SENSITIVE_TAG_NAMES_MAP=i},73217,(e,t,a)=>{let n=e.r(86863),i=e.r(99235),r=RegExp("\r","g"),s=`__HTML_DOM_PARSER_CARRIAGE_RETURN_PLACEHOLDER_${Date.now().toString()}__`,l=RegExp(s,"g");function o(e){return e.replace(l,"\r")}a.escapeSpecialCharacters=function(e){return e.replace(r,s)},a.formatDOM=function e(t,a=null,r){let s,l=[],d=0,c=t.length;for(;d<c;d++){let r=t[d];switch(r.nodeType){case 1:{let t=function(e){var t;let a=(t=e=e.toLowerCase(),i.CASE_SENSITIVE_TAG_NAMES_MAP[t]);return a||e}(r.nodeName);(s=new n.Element(t,function(e){let t={},a=0,n=e.length;for(;a<n;a++){let n=e[a];t[n.name]=n.value}return t}(r.attributes))).children=e("template"===t?r.content.childNodes:r.childNodes,s);break}case 3:s=new n.Text(o(r.nodeValue??""));break;case 8:s=new n.Comment(r.nodeValue??"");break;default:continue}let c=l[d-1]??null;c&&(c.next=s),s.parent=a,s.prev=c,s.next=null,l.push(s)}return r&&((s=new n.ProcessingInstruction(r.substring(0,r.indexOf(" ")).toLowerCase(),r)).next=l[0]??null,s.parent=a,l.unshift(s),l[1]&&(l[1].prev=l[0])),l},a.hasOpenTag=function(e,t){let a="<"+t,n=e.toLowerCase().indexOf(a);if(-1===n)return!1;let i=e[n+a.length];return">"===i||" "===i||"	"===i||"\n"===i||"\r"===i||"/"===i},a.revertEscapedCharacters=o},12369,(e,t,a)=>{let n,i=e.r(73217),r="html",s="head",l="body",o=/<([a-zA-Z]+[0-9]?)/;function d(e,t){return t?t.createHTML(e):e}let c=(e,t,a)=>{throw Error("This browser does not support `document.implementation.createHTMLDocument`")},u=(e,t,a)=>{throw Error("This browser does not support `DOMParser.prototype.parseFromString`")},p="object"==typeof window&&window.DOMParser;if("function"==typeof p){let e=new p;c=u=(t,a,n)=>(a&&(t=`<${a}>${t}</${a}>`),e.parseFromString(t,"text/html"))}if("object"==typeof document&&document.implementation){let e=document.implementation.createHTMLDocument();c=function(t,a,n){if(a){let i=e.documentElement.querySelector(a);return i&&(i.innerHTML=d(t,n)),e}return e.documentElement.innerHTML=d(t,n),e}}let h="object"==typeof document&&document.createElement("template");h&&h.content&&(n=(e,t)=>(h.innerHTML=d(e,t),h.content.childNodes)),a.default=function(e,t){e=i.escapeSpecialCharacters(e);let a=o.exec(e)?.[1]?.toLowerCase();switch(a){case r:{let t=u(e);if(!i.hasOpenTag(e,s)){let e=t.querySelector(s);e?.parentNode?.removeChild(e)}if(!i.hasOpenTag(e,l)){let e=t.querySelector(l);e?.parentNode?.removeChild(e)}return t.querySelectorAll(r)}case s:case l:{let n=c(e,void 0,t).querySelectorAll(a);if(i.hasOpenTag(e,l)&&i.hasOpenTag(e,s))return n[0].parentNode?.childNodes??document.createDocumentFragment().childNodes;return n}default:if(n)return n(e,t);return c(e,l,t).querySelector(l)?.childNodes??document.createDocumentFragment().childNodes}},a.getHTMLForInnerHTML=d},71883,(e,t,a)=>{Object.defineProperties(a,{__esModule:{value:!0},[Symbol.toStringTag]:{value:"Module"}});let n=e.r(73217),i=e.r(12369),r=/<(![a-zA-Z\s]+)>/;a.default=function(e,t){if("string"!=typeof e)throw TypeError("First argument must be a string");if(!e)return[];let a=r.exec(e),s=a?a[1]:void 0;return n.formatDOM(i.default(e,t?.trustedTypePolicy),null,s)}},22851,(e,t,a)=>{a.SAME=0,a.CAMELCASE=1,a.possibleStandardNames={accept:0,acceptCharset:1,"accept-charset":"acceptCharset",accessKey:1,action:0,allowFullScreen:1,alt:0,as:0,async:0,autoCapitalize:1,autoComplete:1,autoCorrect:1,autoFocus:1,autoPlay:1,autoSave:1,capture:0,cellPadding:1,cellSpacing:1,challenge:0,charSet:1,checked:0,children:0,cite:0,class:"className",classID:1,className:1,cols:0,colSpan:1,content:0,contentEditable:1,contextMenu:1,controls:0,controlsList:1,coords:0,crossOrigin:1,dangerouslySetInnerHTML:1,data:0,dateTime:1,default:0,defaultChecked:1,defaultValue:1,defer:0,dir:0,disabled:0,disablePictureInPicture:1,disableRemotePlayback:1,download:0,draggable:0,encType:1,enterKeyHint:1,for:"htmlFor",form:0,formMethod:1,formAction:1,formEncType:1,formNoValidate:1,formTarget:1,frameBorder:1,headers:0,height:0,hidden:0,high:0,href:0,hrefLang:1,htmlFor:1,httpEquiv:1,"http-equiv":"httpEquiv",icon:0,id:0,innerHTML:1,inputMode:1,integrity:0,is:0,itemID:1,itemProp:1,itemRef:1,itemScope:1,itemType:1,keyParams:1,keyType:1,kind:0,label:0,lang:0,list:0,loop:0,low:0,manifest:0,marginWidth:1,marginHeight:1,max:0,maxLength:1,media:0,mediaGroup:1,method:0,min:0,minLength:1,multiple:0,muted:0,name:0,noModule:1,nonce:0,noValidate:1,open:0,optimum:0,pattern:0,placeholder:0,playsInline:1,poster:0,preload:0,profile:0,radioGroup:1,readOnly:1,referrerPolicy:1,rel:0,required:0,reversed:0,role:0,rows:0,rowSpan:1,sandbox:0,scope:0,scoped:0,scrolling:0,seamless:0,selected:0,shape:0,size:0,sizes:0,span:0,spellCheck:1,src:0,srcDoc:1,srcLang:1,srcSet:1,start:0,step:0,style:0,summary:0,tabIndex:1,target:0,title:0,type:0,useMap:1,value:0,width:0,wmode:0,wrap:0,about:0,accentHeight:1,"accent-height":"accentHeight",accumulate:0,additive:0,alignmentBaseline:1,"alignment-baseline":"alignmentBaseline",allowReorder:1,alphabetic:0,amplitude:0,arabicForm:1,"arabic-form":"arabicForm",ascent:0,attributeName:1,attributeType:1,autoReverse:1,azimuth:0,baseFrequency:1,baselineShift:1,"baseline-shift":"baselineShift",baseProfile:1,bbox:0,begin:0,bias:0,by:0,calcMode:1,capHeight:1,"cap-height":"capHeight",clip:0,clipPath:1,"clip-path":"clipPath",clipPathUnits:1,clipRule:1,"clip-rule":"clipRule",color:0,colorInterpolation:1,"color-interpolation":"colorInterpolation",colorInterpolationFilters:1,"color-interpolation-filters":"colorInterpolationFilters",colorProfile:1,"color-profile":"colorProfile",colorRendering:1,"color-rendering":"colorRendering",contentScriptType:1,contentStyleType:1,cursor:0,cx:0,cy:0,d:0,datatype:0,decelerate:0,descent:0,diffuseConstant:1,direction:0,display:0,divisor:0,dominantBaseline:1,"dominant-baseline":"dominantBaseline",dur:0,dx:0,dy:0,edgeMode:1,elevation:0,enableBackground:1,"enable-background":"enableBackground",end:0,exponent:0,externalResourcesRequired:1,fill:0,fillOpacity:1,"fill-opacity":"fillOpacity",fillRule:1,"fill-rule":"fillRule",filter:0,filterRes:1,filterUnits:1,floodOpacity:1,"flood-opacity":"floodOpacity",floodColor:1,"flood-color":"floodColor",focusable:0,fontFamily:1,"font-family":"fontFamily",fontSize:1,"font-size":"fontSize",fontSizeAdjust:1,"font-size-adjust":"fontSizeAdjust",fontStretch:1,"font-stretch":"fontStretch",fontStyle:1,"font-style":"fontStyle",fontVariant:1,"font-variant":"fontVariant",fontWeight:1,"font-weight":"fontWeight",format:0,from:0,fx:0,fy:0,g1:0,g2:0,glyphName:1,"glyph-name":"glyphName",glyphOrientationHorizontal:1,"glyph-orientation-horizontal":"glyphOrientationHorizontal",glyphOrientationVertical:1,"glyph-orientation-vertical":"glyphOrientationVertical",glyphRef:1,gradientTransform:1,gradientUnits:1,hanging:0,horizAdvX:1,"horiz-adv-x":"horizAdvX",horizOriginX:1,"horiz-origin-x":"horizOriginX",ideographic:0,imageRendering:1,"image-rendering":"imageRendering",in2:0,in:0,inlist:0,intercept:0,k1:0,k2:0,k3:0,k4:0,k:0,kernelMatrix:1,kernelUnitLength:1,kerning:0,keyPoints:1,keySplines:1,keyTimes:1,lengthAdjust:1,letterSpacing:1,"letter-spacing":"letterSpacing",lightingColor:1,"lighting-color":"lightingColor",limitingConeAngle:1,local:0,markerEnd:1,"marker-end":"markerEnd",markerHeight:1,markerMid:1,"marker-mid":"markerMid",markerStart:1,"marker-start":"markerStart",markerUnits:1,markerWidth:1,mask:0,maskContentUnits:1,maskUnits:1,mathematical:0,mode:0,numOctaves:1,offset:0,opacity:0,operator:0,order:0,orient:0,orientation:0,origin:0,overflow:0,overlinePosition:1,"overline-position":"overlinePosition",overlineThickness:1,"overline-thickness":"overlineThickness",paintOrder:1,"paint-order":"paintOrder",panose1:0,"panose-1":"panose1",pathLength:1,patternContentUnits:1,patternTransform:1,patternUnits:1,pointerEvents:1,"pointer-events":"pointerEvents",points:0,pointsAtX:1,pointsAtY:1,pointsAtZ:1,prefix:0,preserveAlpha:1,preserveAspectRatio:1,primitiveUnits:1,property:0,r:0,radius:0,refX:1,refY:1,renderingIntent:1,"rendering-intent":"renderingIntent",repeatCount:1,repeatDur:1,requiredExtensions:1,requiredFeatures:1,resource:0,restart:0,result:0,results:0,rotate:0,rx:0,ry:0,scale:0,security:0,seed:0,shapeRendering:1,"shape-rendering":"shapeRendering",slope:0,spacing:0,specularConstant:1,specularExponent:1,speed:0,spreadMethod:1,startOffset:1,stdDeviation:1,stemh:0,stemv:0,stitchTiles:1,stopColor:1,"stop-color":"stopColor",stopOpacity:1,"stop-opacity":"stopOpacity",strikethroughPosition:1,"strikethrough-position":"strikethroughPosition",strikethroughThickness:1,"strikethrough-thickness":"strikethroughThickness",string:0,stroke:0,strokeDasharray:1,"stroke-dasharray":"strokeDasharray",strokeDashoffset:1,"stroke-dashoffset":"strokeDashoffset",strokeLinecap:1,"stroke-linecap":"strokeLinecap",strokeLinejoin:1,"stroke-linejoin":"strokeLinejoin",strokeMiterlimit:1,"stroke-miterlimit":"strokeMiterlimit",strokeWidth:1,"stroke-width":"strokeWidth",strokeOpacity:1,"stroke-opacity":"strokeOpacity",suppressContentEditableWarning:1,suppressHydrationWarning:1,surfaceScale:1,systemLanguage:1,tableValues:1,targetX:1,targetY:1,textAnchor:1,"text-anchor":"textAnchor",textDecoration:1,"text-decoration":"textDecoration",textLength:1,textRendering:1,"text-rendering":"textRendering",to:0,transform:0,typeof:0,u1:0,u2:0,underlinePosition:1,"underline-position":"underlinePosition",underlineThickness:1,"underline-thickness":"underlineThickness",unicode:0,unicodeBidi:1,"unicode-bidi":"unicodeBidi",unicodeRange:1,"unicode-range":"unicodeRange",unitsPerEm:1,"units-per-em":"unitsPerEm",unselectable:0,vAlphabetic:1,"v-alphabetic":"vAlphabetic",values:0,vectorEffect:1,"vector-effect":"vectorEffect",version:0,vertAdvY:1,"vert-adv-y":"vertAdvY",vertOriginX:1,"vert-origin-x":"vertOriginX",vertOriginY:1,"vert-origin-y":"vertOriginY",vHanging:1,"v-hanging":"vHanging",vIdeographic:1,"v-ideographic":"vIdeographic",viewBox:1,viewTarget:1,visibility:0,vMathematical:1,"v-mathematical":"vMathematical",vocab:0,widths:0,wordSpacing:1,"word-spacing":"wordSpacing",writingMode:1,"writing-mode":"writingMode",x1:0,x2:0,x:0,xChannelSelector:1,xHeight:1,"x-height":"xHeight",xlinkActuate:1,"xlink:actuate":"xlinkActuate",xlinkArcrole:1,"xlink:arcrole":"xlinkArcrole",xlinkHref:1,"xlink:href":"xlinkHref",xlinkRole:1,"xlink:role":"xlinkRole",xlinkShow:1,"xlink:show":"xlinkShow",xlinkTitle:1,"xlink:title":"xlinkTitle",xlinkType:1,"xlink:type":"xlinkType",xmlBase:1,"xml:base":"xmlBase",xmlLang:1,"xml:lang":"xmlLang",xmlns:0,"xml:space":"xmlSpace",xmlnsXlink:1,"xmlns:xlink":"xmlnsXlink",xmlSpace:1,y1:0,y2:0,y:0,yChannelSelector:1,z:0,zoomAndPan:1}},94555,(e,t,a)=>{"use strict";function n(e,t,a,n,i,r,s){this.acceptsBooleans=2===t||3===t||4===t,this.attributeName=n,this.attributeNamespace=i,this.mustUseProperty=a,this.propertyName=e,this.type=t,this.sanitizeURL=r,this.removeEmptyString=s}let i={};["children","dangerouslySetInnerHTML","defaultValue","defaultChecked","innerHTML","suppressContentEditableWarning","suppressHydrationWarning","style"].forEach(e=>{i[e]=new n(e,0,!1,e,null,!1,!1)}),[["acceptCharset","accept-charset"],["className","class"],["htmlFor","for"],["httpEquiv","http-equiv"]].forEach(([e,t])=>{i[e]=new n(e,1,!1,t,null,!1,!1)}),["contentEditable","draggable","spellCheck","value"].forEach(e=>{i[e]=new n(e,2,!1,e.toLowerCase(),null,!1,!1)}),["autoReverse","externalResourcesRequired","focusable","preserveAlpha"].forEach(e=>{i[e]=new n(e,2,!1,e,null,!1,!1)}),["allowFullScreen","async","autoFocus","autoPlay","controls","default","defer","disabled","disablePictureInPicture","disableRemotePlayback","formNoValidate","hidden","loop","noModule","noValidate","open","playsInline","readOnly","required","reversed","scoped","seamless","itemScope"].forEach(e=>{i[e]=new n(e,3,!1,e.toLowerCase(),null,!1,!1)}),["checked","multiple","muted","selected"].forEach(e=>{i[e]=new n(e,3,!0,e,null,!1,!1)}),["capture","download"].forEach(e=>{i[e]=new n(e,4,!1,e,null,!1,!1)}),["cols","rows","size","span"].forEach(e=>{i[e]=new n(e,6,!1,e,null,!1,!1)}),["rowSpan","start"].forEach(e=>{i[e]=new n(e,5,!1,e.toLowerCase(),null,!1,!1)});let r=/[\-\:]([a-z])/g,s=e=>e[1].toUpperCase();["accent-height","alignment-baseline","arabic-form","baseline-shift","cap-height","clip-path","clip-rule","color-interpolation","color-interpolation-filters","color-profile","color-rendering","dominant-baseline","enable-background","fill-opacity","fill-rule","flood-color","flood-opacity","font-family","font-size","font-size-adjust","font-stretch","font-style","font-variant","font-weight","glyph-name","glyph-orientation-horizontal","glyph-orientation-vertical","horiz-adv-x","horiz-origin-x","image-rendering","letter-spacing","lighting-color","marker-end","marker-mid","marker-start","overline-position","overline-thickness","paint-order","panose-1","pointer-events","rendering-intent","shape-rendering","stop-color","stop-opacity","strikethrough-position","strikethrough-thickness","stroke-dasharray","stroke-dashoffset","stroke-linecap","stroke-linejoin","stroke-miterlimit","stroke-opacity","stroke-width","text-anchor","text-decoration","text-rendering","underline-position","underline-thickness","unicode-bidi","unicode-range","units-per-em","v-alphabetic","v-hanging","v-ideographic","v-mathematical","vector-effect","vert-adv-y","vert-origin-x","vert-origin-y","word-spacing","writing-mode","xmlns:xlink","x-height"].forEach(e=>{let t=e.replace(r,s);i[t]=new n(t,1,!1,e,null,!1,!1)}),["xlink:actuate","xlink:arcrole","xlink:role","xlink:show","xlink:title","xlink:type"].forEach(e=>{let t=e.replace(r,s);i[t]=new n(t,1,!1,e,"http://www.w3.org/1999/xlink",!1,!1)}),["xml:base","xml:lang","xml:space"].forEach(e=>{let t=e.replace(r,s);i[t]=new n(t,1,!1,e,"http://www.w3.org/XML/1998/namespace",!1,!1)}),["tabIndex","crossOrigin"].forEach(e=>{i[e]=new n(e,1,!1,e.toLowerCase(),null,!1,!1)}),i.xlinkHref=new n("xlinkHref",1,!1,"xlink:href","http://www.w3.org/1999/xlink",!0,!1),["src","href","action","formAction"].forEach(e=>{i[e]=new n(e,1,!1,e.toLowerCase(),null,!0,!0)});let{CAMELCASE:l,SAME:o,possibleStandardNames:d}=e.r(22851),c=RegExp.prototype.test.bind(RegExp("^(data|aria)-[:A-Z_a-z\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u02FF\\u0370-\\u037D\\u037F-\\u1FFF\\u200C-\\u200D\\u2070-\\u218F\\u2C00-\\u2FEF\\u3001-\\uD7FF\\uF900-\\uFDCF\\uFDF0-\\uFFFD\\-.0-9\\u00B7\\u0300-\\u036F\\u203F-\\u2040]*$")),u=Object.keys(d).reduce((e,t)=>{let a=d[t];return a===o?e[t]=t:a===l?e[t.toLowerCase()]=t:e[t]=a,e},{});a.BOOLEAN=3,a.BOOLEANISH_STRING=2,a.NUMERIC=5,a.OVERLOADED_BOOLEAN=4,a.POSITIVE_NUMERIC=6,a.RESERVED=0,a.STRING=1,a.getPropertyInfo=function(e){return i.hasOwnProperty(e)?i[e]:null},a.isCustomAttribute=c,a.possibleStandardNames=u},26568,(e,t,a)=>{"use strict";var n=/\/\*(?:[^*]|\*(?!\/))*\*\//g,i=/\n/g,r=/^\s*/,s=/^(\*?[-#/*\\\w]+(\[[0-9a-z_-]+\])?)\s*/,l=/^:\s*/,o=/^((?:'(?:\\.|[^'\\])*'|"(?:\\.|[^"\\])*"|url\((?:'(?:\\.|[^'\\])*'|"(?:\\.|[^"\\])*"|[^)]*)\)|[^};])+)/,d=/^[;\s]*/;t.exports=function(e,t){if("string"!=typeof e)throw TypeError("First argument must be a string");if(!e)return[];t=t||{};var a=1,c=1;function u(e){var t=e.match(i);t&&(a+=t.length);var n=e.lastIndexOf("\n");c=~n?e.length-n:c+e.length}function p(){var e={line:a,column:c};return function(t){return t.position=new h(e),f(r),t}}function h(e){this.start=e,this.end={line:a,column:c},this.source=t.source}function m(n){var i=Error(t.source+":"+a+":"+c+": "+n);if(i.reason=n,i.filename=t.source,i.line=a,i.column=c,i.source=e,t.silent);else throw i}function f(t){var a=t.exec(e);if(a){var n=a[0];return u(n),e=e.slice(n.length),a}}function g(e){for(var t;t=b();)e.push(t);return e}function b(){var t=p();if("/"==e.charAt(0)&&"*"==e.charAt(1)){for(var a=2;""!=e.charAt(a)&&("*"!=e.charAt(a)||"/"!=e.charAt(a+1));)++a;if(a+=2,""===e.charAt(a-1))return m("End of comment missing");var n=e.slice(2,a-2);return c+=2,u(n),e=e.slice(a),c+=2,t({type:"comment",comment:n})}}h.prototype.content=e,f(r);var w,x=[];for(g(x);w=function(){var e=p(),t=f(s);if(t){if(b(),!f(l))return m("property missing ':'");var a=f(o),i=e({type:"declaration",property:t[0].replace(n,"").trim(),value:a?a[0].replace(n,"").trim():""});return f(d),i}}();)x.push(w),g(x);return x}},70454,(e,t,a)=>{let n,i;var r=Object.create,s=Object.defineProperty,l=Object.getOwnPropertyDescriptor,o=Object.getOwnPropertyNames,d=Object.getPrototypeOf,c=Object.prototype.hasOwnProperty;let u=e.r(26568);i=null!=(n=u)?r(d(n)):{},u=((e,t,a)=>{if(t&&"object"==typeof t||"function"==typeof t)for(var n,i=o(t),r=0,d=i.length;r<d;r++)n=i[r],c.call(e,n)||void 0===n||s(e,n,{get:(e=>t[e]).bind(null,n),enumerable:!(a=l(t,n))||a.enumerable});return e})(n&&n.__esModule?i:s(i,"default",{value:n,enumerable:!0}),n),t.exports=function(e,t){let a=null;if(!e||"string"!=typeof e)return a;let n=(0,u.default)(e),i="function"==typeof t;return n.forEach(e=>{if("declaration"!==e.type)return;let{property:n,value:r}=e;i?t(n,r,e):r&&((a=a??{})[n]=r)}),a}},3187,(e,t,a)=>{let n,i;var r=Object.create,s=Object.defineProperty,l=Object.getOwnPropertyDescriptor,o=Object.getOwnPropertyNames,d=Object.getPrototypeOf,c=Object.prototype.hasOwnProperty;let u=e.r(70454);i=null!=(n=u)?r(d(n)):{},u=((e,t,a)=>{if(t&&"object"==typeof t||"function"==typeof t)for(var n,i=o(t),r=0,d=i.length;r<d;r++)n=i[r],c.call(e,n)||void 0===n||s(e,n,{get:(e=>t[e]).bind(null,n),enumerable:!(a=l(t,n))||a.enumerable});return e})(n&&n.__esModule?i:s(i,"default",{value:n,enumerable:!0}),n);let p=/^--[a-zA-Z0-9_-]+$/,h=/-([a-z])/g,m=/^[^-]+$/,f=/^-(webkit|moz|ms|o|khtml)-/,g=/^-(ms)-/,b=(e,t)=>t.toUpperCase(),w=(e,t)=>`${t}-`;t.exports=function(e,t){let a={};return e&&"string"==typeof e&&(0,u.default)(e,(e,n)=>{e&&n&&(a[((e,t={})=>{let a;return!(a=e)||m.test(a)||p.test(a)?e:(e=e.toLowerCase(),(e=t.reactCompat?e.replace(g,w):e.replace(f,w)).replace(h,b))})(e,t)]=n)}),a}},46335,(e,t,a)=>{"use strict";var n=e.e&&e.e.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};Object.defineProperty(a,"__esModule",{value:!0}),a.returnFirstArg=a.canTextBeChildOfNode=a.ELEMENTS_WITH_NO_TEXT_CHILDREN=a.PRESERVE_CUSTOM_ATTRIBUTES=void 0,a.isCustomComponent=function(e,t){return e.includes("-")?!s.has(e):!!(t&&"string"==typeof t.is)},a.setStyleProp=function(e,t){if("string"==typeof e){if(!e.trim()){t.style={};return}try{t.style=(0,r.default)(e,l)}catch(e){t.style={}}}};let i=e.r(71645),r=n(e.r(3187)),s=new Set(["annotation-xml","color-profile","font-face","font-face-src","font-face-uri","font-face-format","font-face-name","missing-glyph"]),l={reactCompat:!0};a.PRESERVE_CUSTOM_ATTRIBUTES=Number(i.version.split(".")[0])>=16,a.ELEMENTS_WITH_NO_TEXT_CHILDREN=new Set(["tr","tbody","thead","tfoot","colgroup","table","head","html","frameset"]),a.canTextBeChildOfNode=e=>!a.ELEMENTS_WITH_NO_TEXT_CHILDREN.has(e.name),a.returnFirstArg=e=>e},12467,(e,t,a)=>{"use strict";Object.defineProperty(a,"__esModule",{value:!0}),a.default=function(e={},t){let a={},d=!!(e.type&&l[e.type]);for(let l in e){let c=e[l];if((0,n.isCustomAttribute)(l)){a[l]=c;continue}let u=l.toLowerCase(),p=o(u);if(p){let e=(0,n.getPropertyInfo)(p);switch(r.includes(p)&&s.includes(t)&&!d&&(p=o("default"+u)),a[p]=c,null==e?void 0:e.type){case n.BOOLEAN:a[p]=!0;break;case n.OVERLOADED_BOOLEAN:""===c&&(a[p]=!0)}continue}i.PRESERVE_CUSTOM_ATTRIBUTES&&(a[l]=c)}return(0,i.setStyleProp)(e.style,a),a};let n=e.r(94555),i=e.r(46335),r=["checked","value"],s=["input","select","textarea"],l={reset:!0,submit:!0};function o(e){return n.possibleStandardNames[e]}},40597,e=>{"use strict";var t,a;(a=t||(t={})).Root="root",a.Text="text",a.Directive="directive",a.Comment="comment",a.Script="script",a.Style="style",a.Tag="tag",a.CDATA="cdata",a.Doctype="doctype",t.Root,t.Text,t.Directive,t.Comment,t.Script,t.Style,t.Tag,t.CDATA,t.Doctype;class n{parent=null;prev=null;next=null;startIndex=null;endIndex=null;get parentNode(){return this.parent}set parentNode(e){this.parent=e}get previousSibling(){return this.prev}set previousSibling(e){this.prev=e}get nextSibling(){return this.next}set nextSibling(e){this.next=e}cloneNode(e=!1){return x(this,e)}}class i extends n{data;constructor(e){super(),this.data=e}get nodeValue(){return this.data}set nodeValue(e){this.data=e}}class r extends i{type=t.Text;get nodeType(){return 3}}class s extends i{type=t.Comment;get nodeType(){return 8}}class l extends i{type=t.Directive;name;constructor(e,t){super(t),this.name=e}get nodeType(){return 1}"x-name";"x-publicId";"x-systemId"}class o extends n{children;constructor(e){super(),this.children=e}get firstChild(){return this.children[0]??null}get lastChild(){return this.children.length>0?this.children[this.children.length-1]:null}get childNodes(){return this.children}set childNodes(e){this.children=e}}class d extends o{type=t.CDATA;get nodeType(){return 4}}class c extends o{type=t.Root;get nodeType(){return 9}}class u extends o{name;attribs;type;constructor(e,a,n=[],i="script"===e?t.Script:"style"===e?t.Style:t.Tag){super(n),this.name=e,this.attribs=a,this.type=i}get nodeType(){return 1}get tagName(){return this.name}set tagName(e){this.name=e}get attributes(){return Object.keys(this.attribs).map(e=>({name:e,value:this.attribs[e],namespace:this["x-attribsNamespace"]?.[e],prefix:this["x-attribsPrefix"]?.[e]}))}namespace;"x-attribsNamespace";"x-attribsPrefix"}function p(e){return e.type===t.Tag||e.type===t.Script||e.type===t.Style}function h(e){return e.type===t.CDATA}function m(e){return e.type===t.Text}function f(e){return e.type===t.Comment}function g(e){return e.type===t.Directive}function b(e){return e.type===t.Root}function w(e){return Object.hasOwn(e,"children")}function x(e,t=!1){let a;if(m(e))a=new r(e.data);else if(f(e))a=new s(e.data);else if(p(e)){let n=t?v(e.children):[],i=new u(e.name,{...e.attribs},n);for(let e of n)e.parent=i;null!=e.namespace&&(i.namespace=e.namespace),e["x-attribsNamespace"]&&(i["x-attribsNamespace"]={...e["x-attribsNamespace"]}),e["x-attribsPrefix"]&&(i["x-attribsPrefix"]={...e["x-attribsPrefix"]}),a=i}else if(h(e)){let n=t?v(e.children):[],i=new d(n);for(let e of n)e.parent=i;a=i}else if(b(e)){let n=t?v(e.children):[],i=new c(n);for(let e of n)e.parent=i;e["x-mode"]&&(i["x-mode"]=e["x-mode"]),a=i}else if(g(e)){let t=new l(e.name,e.data);null!=e["x-name"]&&(t["x-name"]=e["x-name"],t["x-publicId"]=e["x-publicId"],t["x-systemId"]=e["x-systemId"]),a=t}else throw Error(`Not implemented yet: ${e.type}`);return a.startIndex=e.startIndex,a.endIndex=e.endIndex,null!=e.sourceCodeLocation&&(a.sourceCodeLocation=e.sourceCodeLocation),a}function v(e){let t=e.map(e=>x(e,!0));for(let e=1;e<t.length;e++)t[e].prev=t[e-1],t[e-1].next=t[e];return t}e.s(["CDATA",0,d,"Comment",0,s,"DataNode",0,i,"Document",0,c,"Element",0,u,"Node",0,n,"NodeWithChildren",0,o,"ProcessingInstruction",0,l,"Text",0,r,"cloneNode",0,x,"hasChildren",0,w,"isCDATA",0,h,"isComment",0,f,"isDirective",0,g,"isDocument",0,b,"isTag",0,p,"isText",0,m],28307);let k={withStartIndices:!1,withEndIndices:!1,xmlMode:!1};class y{dom=[];root=new c(this.dom);callback;options;elementCB;done=!1;tagStack=[this.root];lastNode=null;parser=null;constructor(e,t,a){"function"==typeof t&&(a=t,t=k),"object"==typeof e&&(t=e,e=void 0),this.callback=e??null,this.options=t??k,this.elementCB=a??null}onparserinit(e){this.parser=e}onreset(){this.dom=[],this.root=new c(this.dom),this.done=!1,this.tagStack=[this.root],this.lastNode=null,this.parser=null}onend(){this.done||(this.done=!0,this.parser=null,this.handleCallback(null))}onerror(e){this.handleCallback(e)}onclosetag(){this.lastNode=null;let e=this.tagStack.pop();this.options.withEndIndices&&this.parser&&(e.endIndex=this.parser.endIndex),this.elementCB&&this.elementCB(e)}onopentag(e,a){let n=new u(e,a,void 0,this.options.xmlMode?t.Tag:void 0);this.addNode(n),this.tagStack.push(n)}ontext(e){let{lastNode:a}=this;if(a&&a.type===t.Text)a.data+=e,this.options.withEndIndices&&this.parser&&(a.endIndex=this.parser.endIndex);else{let t=new r(e);this.addNode(t),this.lastNode=t}}oncomment(e){if(this.lastNode&&this.lastNode.type===t.Comment){this.lastNode.data+=e;return}let a=new s(e);this.addNode(a),this.lastNode=a}oncommentend(){this.lastNode=null}oncdatastart(){let e=new r(""),t=new d([e]);this.addNode(t),e.parent=t,this.lastNode=e}oncdataend(){this.lastNode=null}onprocessinginstruction(e,t){let a=new l(e,t);this.addNode(a)}handleCallback(e){if("function"==typeof this.callback)this.callback(e,this.dom);else if(e)throw e}addNode(e){let t=this.tagStack[this.tagStack.length-1],a=t.children[t.children.length-1];this.options.withStartIndices&&this.parser&&(e.startIndex=this.parser.startIndex),this.options.withEndIndices&&this.parser&&(e.endIndex=this.parser.endIndex),t.children.push(e),a&&(e.prev=a,a.next=e),e.parent=t,this.lastNode=null}}e.s(["DomHandler",0,y,"default",0,y],16170),e.i(16170),e.i(28307),e.s(["CDATA",0,d,"Comment",0,s,"DataNode",0,i,"Document",0,c,"DomHandler",0,y,"Element",0,u,"Node",0,n,"NodeWithChildren",0,o,"ProcessingInstruction",0,l,"Text",0,r,"cloneNode",0,x,"default",0,y,"hasChildren",0,w,"isCDATA",0,h,"isComment",0,f,"isDirective",0,g,"isDocument",0,b,"isTag",0,p,"isText",0,m],40597)},54465,(e,t,a)=>{"use strict";var n=e.e&&e.e.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};Object.defineProperty(a,"__esModule",{value:!0}),a.default=function e(t,a={}){var n,r,d,c,u,p;let h=[],m="function"==typeof a.replace,f=null!=(n=a.transform)?n:l.returnFirstArg,{cloneElement:g,createElement:b,isValidElement:w}=null!=(r=a.library)?r:o,x=t.length;!function e(t){for(let a of t)switch(a.type){case"tag":case"script":case"style":Object.setPrototypeOf(a,i.Element.prototype),e(a.children);break;case"text":Object.setPrototypeOf(a,i.Text.prototype);break;case"comment":Object.setPrototypeOf(a,i.Comment.prototype)}}(t);for(let n=0;n<x;n++){let i,r=t[n];if(m){let e=null==(d=a.replace)?void 0:d.call(a,r,n);if(w(e)){x>1&&(e=g(e,{key:null!=(c=e.key)?c:n})),h.push(f(e,r,n));continue}}if("text"===r.type){let e=!r.data.trim().length;if(e&&r.parent&&!(0,l.canTextBeChildOfNode)(r.parent)||a.trim&&e)continue;h.push(f(r.data,r,n));continue}let o={};switch((p=r,l.PRESERVE_CUSTOM_ATTRIBUTES&&"tag"===p.type&&(0,l.isCustomComponent)(p.name,p.attribs))?((0,l.setStyleProp)(r.attribs.style,r.attribs),o=r.attribs):r.attribs&&(o=(0,s.default)(r.attribs,r.name)),r.type){case"script":case"style":r.children[0]&&(o.dangerouslySetInnerHTML={__html:r.children[0].data});break;case"tag":"textarea"===r.name&&r.children[0]?o.defaultValue=r.children[0].data:(null==(u=r.children)?void 0:u.length)&&(i=e(r.children,a));break;default:continue}x>1&&(o.key=n),h.push(f(b(r.name,o,i),r,n))}return 1===h.length?h[0]:h};let i=e.r(40597),r=e.r(71645),s=n(e.r(12467)),l=e.r(46335),o={cloneElement:r.cloneElement,createElement:r.createElement,isValidElement:r.isValidElement}},17870,(e,t,a)=>{"use strict";var n=e.e&&e.e.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};Object.defineProperty(a,"__esModule",{value:!0}),a.htmlToDOM=a.domToReact=a.attributesToProps=a.Text=a.ProcessingInstruction=a.Element=a.Comment=void 0,a.default=function(e,t){var a;if("string"!=typeof e)throw TypeError("First argument must be a string");if(!e)return[];let n=Object.assign(Object.assign({},null!=(a=null==t?void 0:t.htmlparser2)?a:l),{trustedTypePolicy:null==t?void 0:t.trustedTypePolicy});return(0,r.default)((0,i.default)(e,n),t)};let i=n(e.r(71883));a.htmlToDOM=i.default,a.attributesToProps=n(e.r(12467)).default;let r=n(e.r(54465));a.domToReact=r.default;var s=e.r(40597);Object.defineProperty(a,"Comment",{enumerable:!0,get:function(){return s.Comment}}),Object.defineProperty(a,"Element",{enumerable:!0,get:function(){return s.Element}}),Object.defineProperty(a,"ProcessingInstruction",{enumerable:!0,get:function(){return s.ProcessingInstruction}}),Object.defineProperty(a,"Text",{enumerable:!0,get:function(){return s.Text}});let l={lowerCaseAttributeNames:!1}},95014,(e,t,a)=>{t.exports=function(e,t,a){var n=-1,i=e.length;t<0&&(t=-t>i?0:i+t),(a=a>i?i:a)<0&&(a+=i),i=t>a?0:a-t>>>0,t>>>=0;for(var r=Array(i);++n<i;)r[n]=e[n+t];return r}},84357,(e,t,a)=>{var n=e.r(95014);t.exports=function(e,t,a){var i=e.length;return a=void 0===a?i:a,!t&&a>=i?e:n(e,t,a)}},79589,(e,t,a)=>{var n=RegExp("[\\u200d\\ud800-\\udfff\\u0300-\\u036f\\ufe20-\\ufe2f\\u20d0-\\u20ff\\ufe0e\\ufe0f]");t.exports=function(e){return n.test(e)}},58672,(e,t,a)=>{t.exports=function(e){return e.split("")}},95365,(e,t,a)=>{var n="\\ud800-\\udfff",i="[\\u0300-\\u036f\\ufe20-\\ufe2f\\u20d0-\\u20ff]",r="\\ud83c[\\udffb-\\udfff]",s="[^"+n+"]",l="(?:\\ud83c[\\udde6-\\uddff]){2}",o="[\\ud800-\\udbff][\\udc00-\\udfff]",d="(?:"+i+"|"+r+")?",c="[\\ufe0e\\ufe0f]?",u="(?:\\u200d(?:"+[s,l,o].join("|")+")"+c+d+")*",p=RegExp(r+"(?="+r+")|"+("(?:"+[s+i+"?",i,l,o,"["+n+"]"].join("|"))+")"+(c+d+u),"g");t.exports=function(e){return e.match(p)||[]}},34170,(e,t,a)=>{var n=e.r(58672),i=e.r(79589),r=e.r(95365);t.exports=function(e){return i(e)?r(e):n(e)}},29821,(e,t,a)=>{var n=e.r(84357),i=e.r(79589),r=e.r(34170),s=e.r(2922);t.exports=function(e){return function(t){var a=i(t=s(t))?r(t):void 0,l=a?a[0]:t.charAt(0),o=a?n(a,1).join(""):t.slice(1);return l[e]()+o}}},32241,(e,t,a)=>{t.exports=e.r(29821)("toUpperCase")},20250,(e,t,a)=>{var n=e.r(2922),i=e.r(32241);t.exports=function(e){return i(n(e).toLowerCase())}},92427,(e,t,a)=>{t.exports=function(e,t,a,n){var i=-1,r=null==e?0:e.length;for(n&&r&&(a=e[++i]);++i<r;)a=t(a,e[i],i,e);return a}},31081,(e,t,a)=>{t.exports=function(e){return function(t){return null==e?void 0:e[t]}}},68252,(e,t,a)=>{t.exports=e.r(31081)({À:"A",Á:"A",Â:"A",Ã:"A",Ä:"A",Å:"A",à:"a",á:"a",â:"a",ã:"a",ä:"a",å:"a",Ç:"C",ç:"c",Ð:"D",ð:"d",È:"E",É:"E",Ê:"E",Ë:"E",è:"e",é:"e",ê:"e",ë:"e",Ì:"I",Í:"I",Î:"I",Ï:"I",ì:"i",í:"i",î:"i",ï:"i",Ñ:"N",ñ:"n",Ò:"O",Ó:"O",Ô:"O",Õ:"O",Ö:"O",Ø:"O",ò:"o",ó:"o",ô:"o",õ:"o",ö:"o",ø:"o",Ù:"U",Ú:"U",Û:"U",Ü:"U",ù:"u",ú:"u",û:"u",ü:"u",Ý:"Y",ý:"y",ÿ:"y",Æ:"Ae",æ:"ae",Þ:"Th",þ:"th",ß:"ss",Ā:"A",Ă:"A",Ą:"A",ā:"a",ă:"a",ą:"a",Ć:"C",Ĉ:"C",Ċ:"C",Č:"C",ć:"c",ĉ:"c",ċ:"c",č:"c",Ď:"D",Đ:"D",ď:"d",đ:"d",Ē:"E",Ĕ:"E",Ė:"E",Ę:"E",Ě:"E",ē:"e",ĕ:"e",ė:"e",ę:"e",ě:"e",Ĝ:"G",Ğ:"G",Ġ:"G",Ģ:"G",ĝ:"g",ğ:"g",ġ:"g",ģ:"g",Ĥ:"H",Ħ:"H",ĥ:"h",ħ:"h",Ĩ:"I",Ī:"I",Ĭ:"I",Į:"I",İ:"I",ĩ:"i",ī:"i",ĭ:"i",į:"i",ı:"i",Ĵ:"J",ĵ:"j",Ķ:"K",ķ:"k",ĸ:"k",Ĺ:"L",Ļ:"L",Ľ:"L",Ŀ:"L",Ł:"L",ĺ:"l",ļ:"l",ľ:"l",ŀ:"l",ł:"l",Ń:"N",Ņ:"N",Ň:"N",Ŋ:"N",ń:"n",ņ:"n",ň:"n",ŋ:"n",Ō:"O",Ŏ:"O",Ő:"O",ō:"o",ŏ:"o",ő:"o",Ŕ:"R",Ŗ:"R",Ř:"R",ŕ:"r",ŗ:"r",ř:"r",Ś:"S",Ŝ:"S",Ş:"S",Š:"S",ś:"s",ŝ:"s",ş:"s",š:"s",Ţ:"T",Ť:"T",Ŧ:"T",ţ:"t",ť:"t",ŧ:"t",Ũ:"U",Ū:"U",Ŭ:"U",Ů:"U",Ű:"U",Ų:"U",ũ:"u",ū:"u",ŭ:"u",ů:"u",ű:"u",ų:"u",Ŵ:"W",ŵ:"w",Ŷ:"Y",ŷ:"y",Ÿ:"Y",Ź:"Z",Ż:"Z",Ž:"Z",ź:"z",ż:"z",ž:"z",Ĳ:"IJ",ĳ:"ij",Œ:"Oe",œ:"oe",ŉ:"'n",ſ:"s"})},73086,(e,t,a)=>{var n=e.r(68252),i=e.r(2922),r=/[\xc0-\xd6\xd8-\xf6\xf8-\xff\u0100-\u017f]/g,s=RegExp("[\\u0300-\\u036f\\ufe20-\\ufe2f\\u20d0-\\u20ff]","g");t.exports=function(e){return(e=i(e))&&e.replace(r,n).replace(s,"")}},40253,(e,t,a)=>{var n=/[^\x00-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f]+/g;t.exports=function(e){return e.match(n)||[]}},74828,(e,t,a)=>{var n=/[a-z][A-Z]|[A-Z]{2}[a-z]|[0-9][a-zA-Z]|[a-zA-Z][0-9]|[^a-zA-Z0-9 ]/;t.exports=function(e){return n.test(e)}},81144,(e,t,a)=>{var n="\\ud800-\\udfff",i="\\u2700-\\u27bf",r="a-z\\xdf-\\xf6\\xf8-\\xff",s="A-Z\\xc0-\\xd6\\xd8-\\xde",l="\\xac\\xb1\\xd7\\xf7\\x00-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\xbf\\u2000-\\u206f \\t\\x0b\\f\\xa0\\ufeff\\n\\r\\u2028\\u2029\\u1680\\u180e\\u2000\\u2001\\u2002\\u2003\\u2004\\u2005\\u2006\\u2007\\u2008\\u2009\\u200a\\u202f\\u205f\\u3000",o="['’]",d="["+l+"]",c="["+r+"]",u="[^"+n+l+"\\d+"+i+r+s+"]",p="(?:\\ud83c[\\udde6-\\uddff]){2}",h="[\\ud800-\\udbff][\\udc00-\\udfff]",m="["+s+"]",f="(?:"+c+"|"+u+")",g="(?:"+m+"|"+u+")",b="(?:"+o+"(?:d|ll|m|re|s|t|ve))?",w="(?:"+o+"(?:D|LL|M|RE|S|T|VE))?",x="(?:[\\u0300-\\u036f\\ufe20-\\ufe2f\\u20d0-\\u20ff]|\\ud83c[\\udffb-\\udfff])?",v="[\\ufe0e\\ufe0f]?",k="(?:\\u200d(?:"+["[^"+n+"]",p,h].join("|")+")"+v+x+")*",y="(?:"+["["+i+"]",p,h].join("|")+")"+(v+x+k),_=RegExp([m+"?"+c+"+"+b+"(?="+[d,m,"$"].join("|")+")",g+"+"+w+"(?="+[d,m+f,"$"].join("|")+")",m+"?"+f+"+"+b,m+"+"+w,"\\d*(?:1ST|2ND|3RD|(?![123])\\dTH)(?=\\b|[a-z_])|\\d*(?:1st|2nd|3rd|(?![123])\\dth)(?=\\b|[A-Z_])|\\d+",y].join("|"),"g");t.exports=function(e){return e.match(_)||[]}},29548,(e,t,a)=>{var n=e.r(40253),i=e.r(74828),r=e.r(2922),s=e.r(81144);t.exports=function(e,t,a){return(e=r(e),void 0===(t=a?void 0:t))?i(e)?s(e):n(e):e.match(t)||[]}},76168,(e,t,a)=>{var n=e.r(92427),i=e.r(73086),r=e.r(29548),s=RegExp("['’]","g");t.exports=function(e){return function(t){return n(r(i(t).replace(s,"")),e,"")}}},59823,(e,t,a)=>{var n=e.r(20250);t.exports=e.r(76168)(function(e,t,a){return t=t.toLowerCase(),e+(a?n(t):t)})},22906,e=>{"use strict";var t,a,n,i=e.i(57727),r=e.i(71645),s=e.i(45009),l=e.i(85658),o=e.i(8597),d=e.i(2458),c=e.i(13648),u=e.i(7670),p=e.i(1447),h=e.i(553),m=e.i(55323),f=e.i(78564),g=e.i(53488),b=e.i(32451),w=e.i(51221),x=e.i(77141),v=e.i(43476);let k=(0,x.default)((0,v.jsx)("path",{d:"M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"}),"MoreHoriz");var y=e.i(1839);let _=(0,m.styled)(y.default,{name:"MuiBreadcrumbCollapsed"})((0,w.default)(({theme:e})=>({display:"flex",marginLeft:`calc(${e.spacing(1)} * 0.5)`,marginRight:`calc(${e.spacing(1)} * 0.5)`,..."light"===e.palette.mode?{backgroundColor:e.palette.grey[100],color:e.palette.grey[700]}:{backgroundColor:e.palette.grey[700],color:e.palette.grey[100]},borderRadius:2,"&:hover, &:focus":{..."light"===e.palette.mode?{backgroundColor:e.palette.grey[200]}:{backgroundColor:e.palette.grey[600]}},"&:active":{boxShadow:e.shadows[0],..."light"===e.palette.mode?{backgroundColor:(0,b.emphasize)(e.palette.grey[200],.12)}:{backgroundColor:(0,b.emphasize)(e.palette.grey[600],.12)}}}))),C=(0,m.styled)(k)({width:24,height:16}),S=function(e){let{slots:t={},slotProps:a={},...n}=e,{nativeButton:i,...r}=n;return(0,v.jsx)("li",{children:(0,v.jsx)(_,{focusRipple:!0,...r,ownerState:e,children:(0,v.jsx)(C,{as:t.CollapsedIcon,ownerState:e,...a.collapsedIcon})})})};var j=e.i(64719),T=e.i(69072);function E(e){return(0,T.default)("MuiBreadcrumbs",e)}let N=(0,j.default)("MuiBreadcrumbs",["root","ol","li","separator"]),A=(0,m.styled)(g.default,{name:"MuiBreadcrumbs",slot:"Root",overridesResolver:(e,t)=>[{[`& .${N.li}`]:t.li},t.root]})({}),O=(0,m.styled)("ol",{name:"MuiBreadcrumbs",slot:"Ol"})({display:"flex",flexWrap:"wrap",alignItems:"center",padding:0,margin:0,listStyle:"none"}),L=(0,m.styled)("li",{name:"MuiBreadcrumbs",slot:"Separator"})({display:"flex",userSelect:"none",marginLeft:8,marginRight:8}),M=r.forwardRef(function(e,t){var a,n;let i=(0,f.useDefaultProps)({props:e,name:"MuiBreadcrumbs"}),{children:s,className:l,component:o="nav",slots:d={},slotProps:c={},expandText:m="Show path",itemsAfterCollapse:g=1,itemsBeforeCollapse:b=1,maxItems:w=8,separator:x="/",...k}=i,[y,_]=r.useState(!1),C={...i,component:o,expanded:y,expandText:m,itemsAfterCollapse:g,itemsBeforeCollapse:b,maxItems:w,separator:x},j=(e=>{let{classes:t}=e;return(0,p.default)({root:["root"],li:["li"],ol:["ol"],separator:["separator"]},E,t)})(C),T=(0,h.default)({elementType:d.CollapsedIcon,externalSlotProps:c.collapsedIcon,ownerState:C}),N=r.useRef(null),M=r.Children.toArray(s).filter(e=>r.isValidElement(e)).map((e,t)=>(0,v.jsx)("li",{className:j.li,children:e},`child-${t}`));return(0,v.jsx)(A,{ref:t,component:o,color:"textSecondary",className:(0,u.default)(j.root,l),ownerState:C,...k,children:(0,v.jsx)(O,{className:j.ol,ref:N,ownerState:C,children:(a=y||w&&M.length<=w||b+g>=M.length?M:[...M.slice(0,b),(0,v.jsx)(S,{"aria-label":m,slots:{CollapsedIcon:d.CollapsedIcon},slotProps:{collapsedIcon:T},onClick:()=>{_(!0);let e=N.current.querySelector("a[href],button,[tabindex]");e&&e.focus()}},"ellipsis"),...M.slice(M.length-g,M.length)],n=j.separator,a.reduce((e,t,i)=>(i<a.length-1?e=e.concat(t,(0,v.jsx)(L,{"aria-hidden":!0,className:n,ownerState:C,children:x},`separator-${i}`)):e.push(t),e),[]))})})});var R=e.i(18863),D=e.i(68874),I=e.i(66919),P=e.i(23262),z=e.i(84244),F=e.i(54086),H=e.i(66052),B=e.i(28724),$=e.i(88793),U=e.i(17603),W=e.i(73129),V=e.i(89088),G=e.i(6692),q=e.i(41322),Y=e.i(14259);let X={visibility:"hidden"},K={};function Z(e,t,a,n){let i=function(e,t,a,n=K){let i,r,{resetInlineTransform:s=!0}=n,l=a&&a.getBoundingClientRect(),o=(0,Y.ownerWindow)(t);if(s){let e=t.style.transform,a=t.style.transition;t.style.transition="",t.style.transform="",i=t.getBoundingClientRect(),r=o.getComputedStyle(t).getPropertyValue("transform"),t.style.transform=e,t.style.transition=a}else i=t.getBoundingClientRect(),r=o.getComputedStyle(t).getPropertyValue("transform");let{offsetX:d,offsetY:c}=(0,q.getTranslateOffsets)(r);return"left"===e?l?`translateX(${l.right+d-i.left}px)`:`translateX(${o.innerWidth+d-i.left}px)`:"right"===e?l?`translateX(-${i.right-l.left-d}px)`:`translateX(-${i.left+i.width-d}px)`:"up"===e?l?`translateY(${l.bottom+c-i.top}px)`:`translateY(${o.innerHeight+c-i.top}px)`:l?`translateY(-${i.top-l.top+i.height-c}px)`:`translateY(-${i.top+i.height-c}px)`}(e,t,"function"==typeof a?a():a,n);t.style.transform=i}let J=r.forwardRef(function(e,t){let a=(0,V.useTheme)(),n={enter:a.transitions.easing.easeOut,exit:a.transitions.easing.sharp},i={enter:a.transitions.duration.enteringScreen,exit:a.transitions.duration.leavingScreen},{addEndListener:s,appear:l=!0,children:o,container:d,disablePrefersReducedMotion:c=!1,direction:u="down",easing:p=n,in:h,onEnter:m,onEntered:f,onEntering:g,onExit:b,onExited:w,onExiting:x,style:k,timeout:y=i,..._}=e,C=(0,G.default)(a.motion.reducedMotion,c),S=r.useRef(null),j=r.useRef(!1),T=(0,W.default)((0,B.default)(o),S,t),E=(0,q.normalizedTransitionCallback)(S,(e,t)=>{Z(u,e,d),C.shouldReduceMotion||(0,q.reflow)(e),m&&m(e,t)}),N=(0,q.normalizedTransitionCallback)(S,(e,t)=>{let n=(0,q.getTransitionProps)({timeout:y,style:k,easing:p},{mode:"enter"}),i=C.getTransitionTiming({duration:n.duration,delay:n.delay});e.style.transition=a.transitions.create("transform",{duration:i.duration,easing:n.easing,delay:i.delay}),e.style.transform="none",g&&g(e,t)}),A=(0,q.normalizedTransitionCallback)(S,f),O=(0,q.normalizedTransitionCallback)(S,x),L=(0,q.normalizedTransitionCallback)(S,e=>{var t;let n=(0,q.getTransitionProps)({timeout:y,style:k,easing:p},{mode:"exit"}),i=C.getTransitionTiming({duration:n.duration,delay:n.delay});e.style.transition=a.transitions.create("transform",{duration:i.duration,easing:n.easing,delay:i.delay});let r="string"==typeof(t=e.style.transform)&&/^translate\(.+,\s*.+\)$/.test(t);j.current=r,Z(u,e,d,{resetInlineTransform:!r}),b&&b(e)}),M=(0,q.normalizedTransitionCallback)(S,e=>{j.current=!1,e.style.transition="",w&&w(e)}),R=s?e=>{s(S.current,e)}:void 0,D=r.useCallback(()=>{S.current&&Z(u,S.current,d)},[u,d]);return r.useEffect(()=>{if(h||"down"===u||"right"===u)return;let e=(0,U.default)(()=>{S.current&&Z(u,S.current,d)}),t=(0,Y.ownerWindow)(S.current);return t.addEventListener("resize",e),()=>{e.clear(),t.removeEventListener("resize",e)}},[u,h,d]),r.useEffect(()=>{h||j.current||D()},[h,D]),(0,v.jsx)($.default,{nodeRef:S,onEnter:E,onEntered:A,onEntering:N,onExit:L,onExited:M,onExiting:O,addEndListener:R,appear:l,in:h,reduceMotion:C.shouldReduceMotion,timeout:y,..._,children:(e,{ownerState:t,...a})=>{let n;return n="exited"!==e||h?k&&o.props.style?{...k,...o.props.style}:k||o.props.style:k||o.props.style?{visibility:"hidden",...k,...o.props.style}:X,r.cloneElement(o,{ref:T,style:n,...a})}})});var Q=e.i(7964),Q=Q,ee=Q,et=e.i(84364);function ea(e){return(0,T.default)("MuiSkeleton",e)}(0,j.default)("MuiSkeleton",["root","text","rectangular","rounded","circular","pulse","wave","withChildren","fitContent","heightAuto"]);let en=et.keyframes`
  0% {
    opacity: 1;
  }

  50% {
    opacity: 0.4;
  }

  100% {
    opacity: 1;
  }
`,ei=et.keyframes`
  0% {
    transform: translateX(-100%);
  }

  50% {
    /* +0.5s of delay between each loop */
    transform: translateX(100%);
  }

  100% {
    transform: translateX(100%);
  }
`,er="string"!=typeof en?et.css`
        animation: ${en} 2s ease-in-out 0.5s infinite;
      `:null,es="string"!=typeof ei?et.css`
        &::after {
          animation: ${ei} 2s linear 0.5s infinite;
        }
      `:null,el=(0,m.styled)("span",{name:"MuiSkeleton",slot:"Root",overridesResolver:(e,t)=>{let{ownerState:a}=e;return[t.root,t[a.variant],!1!==a.animation&&t[a.animation],a.hasChildren&&t.withChildren,a.hasChildren&&!a.width&&t.fitContent,a.hasChildren&&!a.height&&t.heightAuto]}})((0,w.default)(({theme:e})=>{let t=(0,Q.getUnit)(e.shape.borderRadius)||"px",a=(0,ee.toUnitless)(e.shape.borderRadius),n=(0,q.getReducedMotionStyles)(e,{animation:"none"}),i=(0,q.getReducedMotionStyles)(e,{"&::after":{animation:"none",display:"none"}});return{display:"block",backgroundColor:e.vars?e.vars.palette.Skeleton.bg:e.alpha(e.palette.text.primary,"light"===e.palette.mode?.11:.13),height:"1.2em",variants:[{props:{variant:"text"},style:{marginTop:0,marginBottom:0,height:"auto",transformOrigin:"0 55%",transform:"scale(1, 0.60)",borderRadius:`${a}${t}/${Math.round(a/.6*10)/10}${t}`,"&:empty:before":{content:'"\\00a0"'}}},{props:{variant:"circular"},style:{borderRadius:"50%"}},{props:{variant:"rounded"},style:{borderRadius:(e.vars||e).shape.borderRadius}},{props:({ownerState:e})=>e.hasChildren,style:{"& > *":{visibility:"hidden"}}},{props:({ownerState:e})=>e.hasChildren&&!e.width,style:{maxWidth:"fit-content"}},{props:({ownerState:e})=>e.hasChildren&&!e.height,style:{height:"auto"}},{props:{animation:"pulse"},style:er||{animation:`${en} 2s ease-in-out 0.5s infinite`}},...n?[{props:{animation:"pulse"},style:n}]:[],{props:{animation:"wave"},style:{position:"relative",overflow:"hidden",WebkitMaskImage:"-webkit-radial-gradient(white, black)","&::after":{background:`linear-gradient(
                90deg,
                transparent,
                ${(e.vars||e).palette.action.hover},
                transparent
              )`,content:'""',position:"absolute",transform:"translateX(-100%)",bottom:0,left:0,right:0,top:0}}},{props:{animation:"wave"},style:es||{"&::after":{animation:`${ei} 2s linear 0.5s infinite`}}},...i?[{props:{animation:"wave"},style:i}]:[]]}})),eo=r.forwardRef(function(e,t){let a=(0,f.useDefaultProps)({props:e,name:"MuiSkeleton"}),{animation:n="pulse",className:i,component:r="span",height:s,style:l,variant:o="text",width:d,...c}=a,h={...a,animation:n,component:r,variant:o,hasChildren:!!c.children},m=(e=>{let{classes:t,variant:a,animation:n,hasChildren:i,width:r,height:s}=e;return(0,p.default)({root:["root",a,n,i&&"withChildren",i&&!r&&"fitContent",i&&!s&&"heightAuto"]},ea,t)})(h);return(0,v.jsx)(el,{as:r,ref:t,className:(0,u.default)(m.root,i),ownerState:h,...c,style:{width:d,height:s,...l}})});var ed=e.i(42032),ec=e.i(33314),eu=e.i(50193);let ep=function(e={}){let{autoHideDuration:t=null,disableWindowBlurListener:a=!1,onClose:n,open:i,resumeHideDuration:s}=e,l=(0,ec.default)();r.useEffect(()=>{if(i)return document.addEventListener("keydown",e),()=>{document.removeEventListener("keydown",e)};function e(e){e.defaultPrevented||"Escape"!==e.key||n?.(e,"escapeKeyDown")}},[i,n]);let o=(0,ed.default)((e,t)=>{n?.(e,t)}),d=(0,ed.default)(e=>{n&&null!=e&&l.start(e,()=>{o(null,"timeout")})});r.useEffect(()=>(i&&d(t),l.clear),[i,t,d,l]);let c=l.clear,u=r.useCallback(()=>{null!=t&&d(null!=s?s:.5*t)},[t,s,d]);return r.useEffect(()=>{if(!a&&i)return window.addEventListener("focus",u),window.addEventListener("blur",c),()=>{window.removeEventListener("focus",u),window.removeEventListener("blur",c)}},[a,i,u,c]),{getRootProps:(t={})=>{let a={...(0,eu.default)(e),...(0,eu.default)(t)};return{role:"presentation",...t,...a,onBlur:e=>{let t=a.onBlur;t?.(e),u()},onFocus:e=>{let t=a.onFocus;t?.(e),c()},onMouseEnter:e=>{let t=a.onMouseEnter;t?.(e),c()},onMouseLeave:e=>{let t=a.onMouseLeave;t?.(e),u()}}},onClickAway:e=>{n?.(e,"clickaway")}}};var eh=e.i(65337),em=e.i(55702),ef=e.i(94141),eg=e.i(56982);function eb(e){return(0,T.default)("MuiSnackbar",e)}(0,j.default)("MuiSnackbar",["root","anchorOriginTopCenter","anchorOriginBottomCenter","anchorOriginTopRight","anchorOriginBottomRight","anchorOriginTopLeft","anchorOriginBottomLeft"]);var ew=e.i(75822);let ex=(0,m.styled)("div",{name:"MuiSnackbar",slot:"Root",overridesResolver:(e,t)=>{let{ownerState:a}=e;return[t.root,t[`anchorOrigin${(0,em.default)(a.anchorOrigin.vertical)}${(0,em.default)(a.anchorOrigin.horizontal)}`]]}})((0,w.default)(({theme:e})=>({zIndex:(e.vars||e).zIndex.snackbar,position:"fixed",display:"flex",left:8,right:8,justifyContent:"center",alignItems:"center",variants:[{props:({ownerState:e})=>"top"===e.anchorOrigin.vertical,style:{top:8,[e.breakpoints.up("sm")]:{top:24}}},{props:({ownerState:e})=>"top"!==e.anchorOrigin.vertical,style:{bottom:8,[e.breakpoints.up("sm")]:{bottom:24}}},{props:({ownerState:e})=>"left"===e.anchorOrigin.horizontal,style:{justifyContent:"flex-start",[e.breakpoints.up("sm")]:{left:24,right:"auto"}}},{props:({ownerState:e})=>"right"===e.anchorOrigin.horizontal,style:{justifyContent:"flex-end",[e.breakpoints.up("sm")]:{right:24,left:"auto"}}},{props:({ownerState:e})=>"center"===e.anchorOrigin.horizontal,style:{[e.breakpoints.up("sm")]:{left:"50%",right:"auto",transform:"translateX(-50%)"}}}]}))),ev=r.forwardRef(function(e,t){let a=(0,f.useDefaultProps)({props:e,name:"MuiSnackbar"}),n=(0,V.useTheme)(),i={enter:n.transitions.duration.enteringScreen,exit:n.transitions.duration.leavingScreen},{action:s,anchorOrigin:{vertical:l,horizontal:o}={vertical:"bottom",horizontal:"left"},autoHideDuration:d=null,children:c,className:u,disableWindowBlurListener:h=!1,message:m,onBlur:g,onClose:b,onFocus:w,onMouseEnter:x,onMouseLeave:k,open:y,resumeHideDuration:_,slots:C={},slotProps:S={},transitionDuration:j=i,...T}=a,E={...a,anchorOrigin:{vertical:l,horizontal:o},autoHideDuration:d,disableWindowBlurListener:h,transitionDuration:j},N=(e=>{let{classes:t,anchorOrigin:a}=e,n={root:["root",`anchorOrigin${(0,em.default)(a.vertical)}${(0,em.default)(a.horizontal)}`]};return(0,p.default)(n,eb,t)})(E),{getRootProps:A,onClickAway:O}=ep(E),[L,M]=r.useState(!0),R={slots:C,slotProps:S},[D,I]=(0,ew.default)("root",{ref:t,className:[N.root,u],elementType:ex,getSlotProps:A,externalForwardedProps:{...R,...T},ownerState:E}),[P,{ownerState:z,...F}]=(0,ew.default)("clickAwayListener",{elementType:eh.default,externalForwardedProps:R,getSlotProps:e=>({onClickAway:(...t)=>{let a=t[0];e.onClickAway?.(...t),a?.defaultMuiPrevented||O(...t)}}),ownerState:E}),[H,B]=(0,ew.default)("content",{elementType:eg.default,shouldForwardComponentProp:!0,externalForwardedProps:R,additionalProps:{message:m,action:s},ownerState:E}),[$,U]=(0,ew.default)("transition",{elementType:ef.default,externalForwardedProps:R,getSlotProps:e=>({onEnter:(...t)=>{e.onEnter?.(...t),M(!1)},onExited:(...t)=>{e.onExited?.(...t),M(!0)}}),additionalProps:{appear:!0,in:y,timeout:j,direction:"top"===l?"down":"up"},ownerState:E});return!y&&L?null:(0,v.jsx)(P,{...F,...C.clickAwayListener&&{ownerState:z},children:(0,v.jsx)(D,{...I,children:(0,v.jsx)($,{...U,children:c||(0,v.jsx)(H,{...B})})})})});var ek=e.i(96914),ey=e.i(58324),e_=e.i(80652),eC=e.i(38405),eS=e.i(36857),ej=e.i(50744);let eT={DRUPAL_THEME_CSS:{name:"drupal-theme.css",url:`${ej.default.getWebHost()}/themes/custom/neon/dist/css/components/theme/theme.css`},DRUPAL_HEADER_JS:{name:"drupal-header.js",url:`${ej.default.getWebHost()}/themes/custom/neon/dist/js/header/header.js`},DRUPAL_HEADER_HTML:{name:"drupal-header.html",url:`${ej.default.getWebHost()}/neon-assets/partial/header`},DRUPAL_FOOTER_HTML:{name:"drupal-footer.html",url:`${ej.default.getWebHost()}/neon-assets/partial/footer`}};Object.keys(eT).forEach(e=>{eT[e].KEY=e});var eE=e.i(34259),eN=e.i(20373),eA=e.i(17870);let eO=eA.default.default||eA.default;var eL=e.i(59823);let eM=`<header class="header" role="banner">
  <div class="header__inner l--offset-wide">
    <div class="header__logo">
  <a href="https://www.neonscience.org" title="Home" rel="home"  id="block-neon-site-branding" class="logo">
    <img src="https://www.neonscience.org/themes/custom/neon/logo.svg" alt="NSF NEON, Operated by Battelle" class="logo__image">
  </a>
</div>

    <input type="checkbox" id="nav-trigger" class="nav-trigger js-mobile-nav-trigger" aria-label="Mobile Navigation Trigger" value="expanded" />
    <label for="nav-trigger">

      <svg class="nav-trigger__icon nav-trigger__icon--expand" xmlns="http://www.w3.org/2000/svg" width="20" height="18" viewBox="0 0 20 18"><path d="M0 0h20v2H0V0zm0 16h20v2H0v-2zm0-8h20v2H0V8z"/></svg>

      <svg class="nav-trigger__icon nav-trigger__icon--collapse" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path d="M1.636.222l14.142 14.142-1.414 1.414L.222 1.636 1.636.222zM.222 14.364L14.364.222l1.414 1.414L1.636 15.778.222 14.364z"/></svg>

    </label>
    <div class="authContainer">
              <div class="openid-connect-login-form" data-drupal-selector="openid-connect-login-form" id="block-openid-connect-login">
  
    
      <form action="/neon-assets/partial/header" method="post" id="openid-connect-login-form" accept-charset="UTF-8">
  <div>

<input data-drupal-selector="edit-openid-connect-client-auth0-login" type="submit" id="edit-openid-connect-client-auth0-login" name="auth0" value="Sign In" class="button js-form-submit form-submit button--search" />
</div><input autocomplete="off" data-drupal-selector="form-qxudjvkel3eutisgzqwyvqborkqnvyd0ke8-shwf0ly" type="hidden" name="form_build_id" value="form-qxudJVkeL3EuTiSGzqwyVqboRkqNvyd0kE8-shwf0lY" />
<input data-drupal-selector="edit-openid-connect-login-form" type="hidden" name="form_id" value="openid_connect_login_form" />

</form>

  </div>

          </div>
    <div class="header__site-navigation">
      <div class="header__menu-main">
        <nav role="navigation" aria-labelledby="block-neon-main-menu-menu" id="block-neon-main-menu">
            
  <h2 class="visually-hidden" id="block-neon-main-menu-menu">Main navigation</h2>
  

        
          <ul  class="menu menu--main" data-depth="0">

              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/about" title="About Us" class="menu__link" data-plugin-id="menu-link-contentcf5cd374-1932-49b4-84fb-c84373c74a2e" data-drupal-link-system-path="node/8793">About</a>

          
                                        <button class='arrow visually-hidden focusable' tabindex='0' aria-label='Toggle Mega Menu'><svg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M9 1L5 5 1 1' stroke='#fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></button>

                            <div class='subNavWrapper'>
                            <div class='innerSubNavWrapper'>
            
                      <ul  class="menu menu--main" data-depth="1">

              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/about/overview" class="menu__link" data-plugin-id="menu-link-content6becd40c-e404-4c45-86a7-b9e351526149" data-drupal-link-system-path="node/10531">NEON Overview</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/visionandmanagement" class="menu__link" data-plugin-id="menu-link-content09549ac0-a6d6-4f7e-9b4c-9a627b5798ca" data-drupal-link-system-path="node/10533">Vision and Management</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/overview/design" class="menu__link" data-plugin-id="menu-link-contentdbaf57e6-8e09-4ced-8067-c328efcbd9d0" data-drupal-link-system-path="node/10532">Spatial and Temporal Design</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/overview/history" class="menu__link" data-plugin-id="menu-link-contenta6cad402-12cf-4d6d-8908-0f84d8fdcc32" data-drupal-link-system-path="node/19">History</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/about-neon-biorepository" class="menu__link" data-plugin-id="menu-link-content531af537-f4a3-44ac-82a2-caca775ba5a5" data-drupal-link-system-path="node/14868">About the NEON Biorepository</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/asu-neon-biorepository-staff" class="menu__link" data-plugin-id="menu-link-content8a58fd73-a95d-456c-8c50-ea70a4cdb0ae" data-drupal-link-system-path="node/14869">ASU NEON Biorepository Staff</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/contact-neon-biorepository" class="menu__link" data-plugin-id="menu-link-contentd5cf0a32-b88e-4fd5-9256-96c56e06126d" data-drupal-link-system-path="node/14006">Contact the NEON Biorepository</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/impact/observatory-blog" class="menu__link" data-plugin-id="menu-link-content8a557196-631b-4be1-b9c5-120bc654861e" data-drupal-link-system-path="node/8857">Observatory Blog</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/newsletter-archive" class="menu__link" data-plugin-id="menu-link-content69db29a4-8a8b-4597-9f2e-3f19cf6db7ad" data-drupal-link-system-path="node/7826">Newsletters</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/staff" class="menu__link" data-plugin-id="menu-link-content7a99e7e0-f51a-4607-ab02-1d3f0056a4e5" data-drupal-link-system-path="node/8802">Staff</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/faq" class="menu__link" data-plugin-id="menu-link-content20c10622-fa09-415a-aeae-c3dfc67c7748" data-drupal-link-system-path="node/10545">FAQ</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/user-accounts" class="menu__link" data-plugin-id="menu-link-contentad23ac3e-1e03-4d2f-8910-83b8fc03fef9" data-drupal-link-system-path="node/8563">User Accounts</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/contact-us" class="menu__link" data-plugin-id="menu-link-contentf8ffd763-5a21-4e18-9626-ae309814d7cf" data-drupal-link-system-path="node/24">Contact Us</a>

                  </li>
      
          </ul>
  

                          </div>

              <div class='subNavLabelWrapper'>
                <button class='subNavClose isDesktop' aria-label='Close Mega Menu'>
                  <svg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M.808 17.778L17.778.808l1.414 1.414-16.97 16.97-1.414-1.414z' fill='#fff'/><path d='M2.222.808l16.97 16.97-1.414 1.414L.808 2.222 2.222.808z' fill='#fff'/></svg>
                </button>
                <h3 class='isDesktop'>About</h3>
                <button class='mobileBack isMobile' aria-label='Back'>
                  <svg width='14' height='10' viewBox='0 0 14 10' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M5 9L1 5l4-4M1 5h12' stroke='#0073CF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                  </svg>
                </button>
              </div>
                            </div>

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/data" class="menu__link" data-plugin-id="menu-link-contentf20b3a84-1e6a-4e28-8360-b1c56a8678de" data-drupal-link-system-path="node/8858">Data</a>

          
                                        <button class='arrow visually-hidden focusable' tabindex='0' aria-label='Toggle Mega Menu'><svg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M9 1L5 5 1 1' stroke='#fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></button>

                            <div class='subNavWrapper'>
                            <div class='innerSubNavWrapper'>
            
                      <ul  class="menu menu--main" data-depth="1">

              <li  class="menu__item menu__item--expanded">
          <a href="https://data.neonscience.org/data-products/explore" class="menu__link" data-plugin-id="menu-link-contentb3fed72f-4451-475a-aaed-5afc65fa5b44">Data Portal</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://data.neonscience.org/visualizations/data-availability" class="menu__link" data-plugin-id="menu-link-content29dc8755-cfe7-4699-ad73-972a8cc6aae6">Data Availability Charts</a>

                  </li>
              <li  class="menu__item">
          <a href="https://data.neonscience.org/data-api" class="menu__link" data-plugin-id="menu-link-content3b2666dd-02d1-4ad2-80ec-77a6c728dc30">API &amp; GraphQL</a>

                  </li>
              <li  class="menu__item">
          <a href="https://data.neonscience.org/prototype-search" class="menu__link" data-plugin-id="menu-link-content304452f2-2aa7-4337-840e-904017a508a2">Prototype Data</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/data-management/externally-hosted-data" class="menu__link" data-plugin-id="menu-link-content1c31d8a5-f543-4481-bc5c-b42102679e1c" data-drupal-link-system-path="node/10552">Externally Hosted Data</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/data-collection-methods" class="menu__link" data-plugin-id="menu-link-content72722bb5-d146-4d66-805f-0da30e1d5658" data-drupal-link-system-path="node/4776">Data Collection Methods</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-collection/airborne-remote-sensing" class="menu__link" data-plugin-id="menu-link-content41b5a0e4-975c-4c08-a67c-0eca84b67ae6" data-drupal-link-system-path="node/3821">Airborne Observation Platform (AOP)</a>

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/data-collection/automated-instruments" class="menu__link" data-plugin-id="menu-link-content393dc0bd-ecea-40be-a9b4-afcdfcc6c328" data-drupal-link-system-path="node/7819">Instrument System (IS)</a>

          
            
                      <ul  class="menu menu--main" data-depth="3">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-collection/instrumented-collection-types" class="menu__link" data-plugin-id="menu-link-content6e8e91a9-749e-4a1d-ac52-cf8f5c0a4cac" data-drupal-link-system-path="node/10900">Instrumented Collection Types</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/aquatic-instrument-system-ais" class="menu__link" data-plugin-id="menu-link-content330c5c00-e652-40c6-9937-1fbd9fe9cdee" data-drupal-link-system-path="node/14872">Aquatic Instrument System (AIS)</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/terrestrial-instrument-system-tis" class="menu__link" data-plugin-id="menu-link-content96b696bc-3be0-4fd7-938c-68a4ad3f7a88" data-drupal-link-system-path="node/14873">Terrestrial Instrument System (TIS)</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/data-collection/observational-sampling" class="menu__link" data-plugin-id="menu-link-contente6368031-4425-41eb-8ebc-b7b4ec924bc6" data-drupal-link-system-path="node/7820">Observational System (OS)</a>

          
            
                      <ul  class="menu menu--main" data-depth="3">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-collection/observation-types" class="menu__link" data-plugin-id="menu-link-content87627456-39bd-4415-a2f7-37c0f67bb854" data-drupal-link-system-path="node/10535">Observation Types</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/data-collection/observational-sampling/site-level-sampling-design" class="menu__link" data-plugin-id="menu-link-content4977367d-9483-461d-9ea7-5a6941736d47" data-drupal-link-system-path="node/10893">Observational Sampling Design</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-collection/sampling-schedules" class="menu__link" data-plugin-id="menu-link-contente43c50bc-9c31-41c5-a780-607d6f3adcc3" data-drupal-link-system-path="node/10889">Sampling Schedules</a>

                  </li>
              <li  class="menu__item">
          <a href="https://data.neonscience.org/taxonomic-lists" class="menu__link" data-plugin-id="menu-link-content4a328a5d-d8ff-453c-9453-8d7d37240bbc">Taxonomic Lists Used by Field Staff</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/optimizing-observational-sampling-designs" class="menu__link" data-plugin-id="menu-link-content0f4cdaa4-35fa-40dd-9a25-61aa940a2d55" data-drupal-link-system-path="node/13121">Optimizing the Observational Sampling Designs</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-collection/protocols-standardized-methods" class="menu__link" data-plugin-id="menu-link-content856ac830-91e7-4bd7-ae3b-0c63cfdcb05a" data-drupal-link-system-path="node/9">Protocols &amp; Standardized Methods</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/resources/getting-started-neon-data-resources" class="menu__link" data-plugin-id="menu-link-contentdc6470d9-df2c-4b92-bfb9-a95a1e18da53" data-drupal-link-system-path="node/11293">Getting Started with NEON Data</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/neonutilities-r-python" class="menu__link" data-plugin-id="menu-link-contente629a26c-36d7-4dea-9e7c-87f1303312d7">neonUtilities for R and Python</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/learning-hub" class="menu__link" data-plugin-id="menu-link-content47208292-0ddb-44d6-b281-272f739fbe4b" data-drupal-link-system-path="node/6136">Learning Hub</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/code-hub" class="menu__link" data-plugin-id="menu-link-contentea6b449c-b803-44e5-a79d-ded34f23c09a" data-drupal-link-system-path="node/7076">Code Hub</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/data/using-data" class="menu__link" data-plugin-id="menu-link-contentdfbf44da-4c25-48ab-83c6-1e7bcbd5e7f1" data-drupal-link-system-path="node/11642">Using Data</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/data-management/data-formats-conventions" class="menu__link" data-plugin-id="menu-link-contentb975385f-187c-44fd-a034-2ce31ee3dd5f" data-drupal-link-system-path="node/10560">Data Formats and Conventions</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/data-management/data-revisions-releases" class="menu__link" data-plugin-id="menu-link-contentdc7e1b39-d62b-4195-8330-817b7ea7ba24" data-drupal-link-system-path="node/10559">Released, Provisional, and Revised Data</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/data-management/data-product-bundles" class="menu__link" data-plugin-id="menu-link-content2e410b73-dc7d-4b62-9e62-967a44257423" data-drupal-link-system-path="node/12601">Data Product Bundles</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/usage-policies" class="menu__link" data-plugin-id="menu-link-content4492166d-5a94-4851-a5a8-0e9d65d5212b" data-drupal-link-system-path="node/11643">Usage Policies</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data/guidelines-policies/citing" class="menu__link" data-plugin-id="menu-link-content5193b582-56f1-4a16-88ab-b20b999f65ba" data-drupal-link-system-path="node/11644">Acknowledging and Citing NEON</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/guidelines-policies/publishing-research-outputs" class="menu__link" data-plugin-id="menu-link-content7c82ebe4-ff75-4229-addb-f35f965575a9" data-drupal-link-system-path="node/11629">Publishing Research Outputs</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/data-notifications" class="menu__link" data-plugin-id="menu-link-content0f1ec38e-7c06-4dc1-a99b-49e9f5e77cea" data-drupal-link-system-path="node/10951">Data Notifications</a>

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/data/data-management" class="menu__link" data-plugin-id="menu-link-content514fa833-7231-4f27-9e3f-b881c6d5ad62" data-drupal-link-system-path="node/10557">NEON Data Management</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/data-management/data-availability" class="menu__link" data-plugin-id="menu-link-content07b048fd-2232-4f71-89ad-42e109a79852" data-drupal-link-system-path="node/10298">Data Availability</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/data-management/data-processing" class="menu__link" data-plugin-id="menu-link-contentc507fd30-8d71-42e6-a7a4-f2ec0f15e0a5" data-drupal-link-system-path="node/8470">Data Processing</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/data-samples/data-management/data-quality-program" class="menu__link" data-plugin-id="menu-link-content45f73191-e5d3-4e38-b12a-2930df20b176" data-drupal-link-system-path="node/10299">Data Quality</a>

                  </li>
      
          </ul>
  

            

                  </li>
      
          </ul>
  

                          </div>

              <div class='subNavLabelWrapper'>
                <button class='subNavClose isDesktop' aria-label='Close Mega Menu'>
                  <svg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M.808 17.778L17.778.808l1.414 1.414-16.97 16.97-1.414-1.414z' fill='#fff'/><path d='M2.222.808l16.97 16.97-1.414 1.414L.808 2.222 2.222.808z' fill='#fff'/></svg>
                </button>
                <h3 class='isDesktop'>Data</h3>
                <button class='mobileBack isMobile' aria-label='Back'>
                  <svg width='14' height='10' viewBox='0 0 14 10' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M5 9L1 5l4-4M1 5h12' stroke='#0073CF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                  </svg>
                </button>
              </div>
                            </div>

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/samples" class="menu__link" data-plugin-id="menu-link-content209fc9e1-01d3-4e02-8fc7-30ee57a8a644" data-drupal-link-system-path="node/10295">Samples &amp; Specimens</a>

          
                                        <button class='arrow visually-hidden focusable' tabindex='0' aria-label='Toggle Mega Menu'><svg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M9 1L5 5 1 1' stroke='#fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></button>

                            <div class='subNavWrapper'>
                            <div class='innerSubNavWrapper'>
            
                      <ul  class="menu menu--main" data-depth="1">

              <li  class="menu__item">
          <a href="https://biorepo.neonscience.org/portal/neon/search/index.php?utm_source=neon_main&amp;utm_medium=directlink" class="menu__link" data-plugin-id="menu-link-content355c07a4-035c-4917-8312-fc81d899d926">NEON Biorepository Sample Portal at ASU</a>

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/about-samples" class="menu__link" data-plugin-id="menu-link-contenta4741ee0-27c0-4596-9801-f0d608f46365" data-drupal-link-system-path="node/14871">About Samples</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://biorepo.neonscience.org/portal/collections/misc/browsecollprofiles.php?utm_source=neon_main&amp;utm_medium=directlink" class="menu__link" data-plugin-id="menu-link-content103dc32e-c90a-4564-a9a5-64ed077a634d">Sample Types</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/samples/sample-repositories" class="menu__link" data-plugin-id="menu-link-contentd05bcdf6-8970-4722-95b8-ca645e64e325" data-drupal-link-system-path="node/10907">Sample Repositories</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/megapit-and-distributed-initial-characterization-soil-archives" class="menu__link" data-plugin-id="menu-link-content5d5cc826-8126-44d5-8b65-3bfc461bbaac" data-drupal-link-system-path="node/14876">Megapit and Distributed Initial Characterization Soil Archives</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/finding-and-accessing-sample-data" class="menu__link" data-plugin-id="menu-link-contentec213025-8d52-43eb-b5d7-61dcb1991385" data-drupal-link-system-path="node/14870">Finding and Accessing Sample Data</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://biorepo.neonscience.org/portal/projects/browsechecklists.php?utm_source=neon_main&amp;utm_medium=directlink" class="menu__link" data-plugin-id="menu-link-content32a2cc35-62ed-4d13-b1ab-7a90ce728d04">Species Checklists</a>

                  </li>
              <li  class="menu__item">
          <a href="https://data.neonscience.org/sample-explorer" class="menu__link" data-plugin-id="menu-link-content5d5dbb38-d1af-4b96-a317-c3342c74824c">Sample Explorer - Relationships and Data</a>

                  </li>
              <li  class="menu__item">
          <a href="https://biorepo.neonscience.org/portal/api/v2/documentation" class="menu__link" data-plugin-id="menu-link-contentaaa3693b-d553-46dc-9772-7a3b23f480fd">Biorepository API</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/requesting-and-using-samples" class="menu__link" data-plugin-id="menu-link-contentfcb7739c-e28e-47db-95d3-ec27c4b068fc" data-drupal-link-system-path="node/14878">Requesting and Using Samples</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://biorepo.neonscience.org/portal/misc/samplerequests.php" class="menu__link" data-plugin-id="menu-link-contentfa143c2b-8afe-4f65-88c2-e3671ab4580f">Requesting Samples from the NEON Biorepository</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/samples/soil-archive" class="menu__link" data-plugin-id="menu-link-content26a9c698-af2e-449d-a9fb-13c6a9f31680" data-drupal-link-system-path="node/5">Request Megapit and Initial Characterization Soil</a>

                  </li>
              <li  class="menu__item">
          <a href="https://biorepo.neonscience.org/portal/misc/sampleguidelines.php" class="menu__link" data-plugin-id="menu-link-content79ef888e-cc9c-4909-b278-bf9f406b88b9">Sample Use Guidelines</a>

                  </li>
              <li  class="menu__item">
          <a href="https://biorepo.neonscience.org/portal/misc/samplepolicy.php" class="menu__link" data-plugin-id="menu-link-content6fa8e139-01bc-4735-99ab-03f4637d2e38">Sample Use Policy</a>

                  </li>
              <li  class="menu__item">
          <a href="https://biorepo.neonscience.org/portal/misc/cite.php" class="menu__link" data-plugin-id="menu-link-contentd63bcbba-09c5-452c-90fe-1e60554793cf">Acknowledging and Citing the NEON Biorepository</a>

                  </li>
      
          </ul>
  

            

                  </li>
      
          </ul>
  

                          </div>

              <div class='subNavLabelWrapper'>
                <button class='subNavClose isDesktop' aria-label='Close Mega Menu'>
                  <svg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M.808 17.778L17.778.808l1.414 1.414-16.97 16.97-1.414-1.414z' fill='#fff'/><path d='M2.222.808l16.97 16.97-1.414 1.414L.808 2.222 2.222.808z' fill='#fff'/></svg>
                </button>
                <h3 class='isDesktop'>Samples &amp; Specimens</h3>
                <button class='mobileBack isMobile' aria-label='Back'>
                  <svg width='14' height='10' viewBox='0 0 14 10' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M5 9L1 5l4-4M1 5h12' stroke='#0073CF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                  </svg>
                </button>
              </div>
                            </div>

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/field-sites" class="menu__link" data-plugin-id="menu-link-content8c902e85-3057-4872-8758-5191bb971f53" data-drupal-link-system-path="node/11376">Field Sites</a>

          
                                        <button class='arrow visually-hidden focusable' tabindex='0' aria-label='Toggle Mega Menu'><svg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M9 1L5 5 1 1' stroke='#fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></button>

                            <div class='subNavWrapper'>
                            <div class='innerSubNavWrapper'>
            
                      <ul  class="menu menu--main" data-depth="1">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/field-site-map-and-info" class="menu__link" data-plugin-id="menu-link-content1f3e6f22-9039-43ee-b6f5-81186dde9817" data-drupal-link-system-path="node/14874">Field Site Map and Info</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/field-sites/spatial-data-maps" class="menu__link" data-plugin-id="menu-link-content28111453-dfce-423a-9b97-f10a8380f666" data-drupal-link-system-path="node/10547">Spatial Data Layers &amp; Maps</a>

                  </li>
      
          </ul>
  

                          </div>

              <div class='subNavLabelWrapper'>
                <button class='subNavClose isDesktop' aria-label='Close Mega Menu'>
                  <svg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M.808 17.778L17.778.808l1.414 1.414-16.97 16.97-1.414-1.414z' fill='#fff'/><path d='M2.222.808l16.97 16.97-1.414 1.414L.808 2.222 2.222.808z' fill='#fff'/></svg>
                </button>
                <h3 class='isDesktop'>Field Sites</h3>
                <button class='mobileBack isMobile' aria-label='Back'>
                  <svg width='14' height='10' viewBox='0 0 14 10' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M5 9L1 5l4-4M1 5h12' stroke='#0073CF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                  </svg>
                </button>
              </div>
                            </div>

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/resources" class="menu__link" data-plugin-id="menu-link-contenta857cb6b-7d79-46c7-9be7-7c5f265721ae" data-drupal-link-system-path="node/6228">Resources</a>

          
                                        <button class='arrow visually-hidden focusable' tabindex='0' aria-label='Toggle Mega Menu'><svg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M9 1L5 5 1 1' stroke='#fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></button>

                            <div class='subNavWrapper'>
                            <div class='innerSubNavWrapper'>
            
                      <ul  class="menu menu--main" data-depth="1">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/getting-started-neon-data-resources" class="menu__link" data-plugin-id="menu-link-contentb4683134-4b70-41b9-bb4f-8f6eea668eb9" data-drupal-link-system-path="node/11293">Getting Started with NEON Data</a>

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/resources/research-support" class="menu__link" data-plugin-id="menu-link-content5af53ebb-9351-4942-989b-0c78514721c1" data-drupal-link-system-path="node/10764">Research Support Services</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/research-support/field-site-coordination" class="menu__link" data-plugin-id="menu-link-content8866c898-b4b5-4be2-b77e-610136832cfd" data-drupal-link-system-path="node/12209">Field Site Coordination</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/research-support/letters-support" class="menu__link" data-plugin-id="menu-link-content80c4026c-822d-4141-a0bd-62592e63a66e" data-drupal-link-system-path="node/8731">Letters of Support</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/research-support/permits-permissions" class="menu__link" data-plugin-id="menu-link-contentc0988e1e-b17b-4967-97fc-6aa3139a9653" data-drupal-link-system-path="node/10769">Permits and Permissions</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/research-support/aop-flight-campaigns" class="menu__link" data-plugin-id="menu-link-contentd8d3542e-4dad-47e2-9f9b-3dc3657c8931" data-drupal-link-system-path="node/7754">AOP Flight Campaigns</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/research-support/research-support-faqs" class="menu__link" data-plugin-id="menu-link-content0020dd25-ba69-4a65-becb-0198653429db" data-drupal-link-system-path="node/11535">Research Support FAQs</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/research-support/nrss-projects" class="menu__link" data-plugin-id="menu-link-contentf54d6f1b-0ebf-46be-be4c-47c6de77c7fc" data-drupal-link-system-path="node/13119">Research Support Projects</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/resources/code-hub" class="menu__link" data-plugin-id="menu-link-content4b099d06-3a5a-4bba-bbd9-ef4efc17094e" data-drupal-link-system-path="node/7076">Code Hub</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/neonutilities-r-and-python" class="menu__link" data-plugin-id="menu-link-contenta8f79003-bfa2-4ade-8157-876e1438b6d9" data-drupal-link-system-path="node/14877">neonUtilities for R and Python</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/code-hub/code-resources-guidelines" class="menu__link" data-plugin-id="menu-link-contentef6dd417-2373-4759-91dc-a24333963b93" data-drupal-link-system-path="node/8619">Code Resources Guidelines</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/code-hub/code-resources-submission" class="menu__link" data-plugin-id="menu-link-content50932777-6688-4cc1-81d2-e15feb8541cb" data-drupal-link-system-path="node/8631">Code Resources Submission</a>

                  </li>
              <li  class="menu__item">
          <a href="https://github.com/neonscience" class="menu__link" data-plugin-id="menu-link-contentb4fa71b6-2b86-42bb-a619-8948decccf4c">NEON&#039;s GitHub Organization Homepage</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/resources/learning-hub" class="menu__link" data-plugin-id="menu-link-contentcb7780e6-23a7-4605-b3da-c94c7a3b7de7" data-drupal-link-system-path="node/6136">Learning Hub</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/learning-hub/tutorials" class="menu__link" data-plugin-id="menu-link-contentf590b3ea-79fe-499c-9647-979af5712923" data-drupal-link-system-path="node/10896">Tutorials</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/learning-hub/workshops-courses" class="menu__link" data-plugin-id="menu-link-content58e3b270-1d4e-47dd-932f-2c8b56514a51" data-drupal-link-system-path="node/6529">Workshops &amp; Courses</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/learning-hub/science-videos" class="menu__link" data-plugin-id="menu-link-content8743bdb2-0a55-458c-a8f8-965d84f52753" data-drupal-link-system-path="node/5345">Science Videos</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/learning-hub/teaching-modules" class="menu__link" data-plugin-id="menu-link-content6ecaa3eb-6d78-4ec6-a4c9-2564154470db" data-drupal-link-system-path="node/10909">Teaching Modules</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/science-seminars-and-data-skills-webinars" class="menu__link" data-plugin-id="menu-link-content72575261-4a4a-4e78-a936-2094fc3ba1c6" data-drupal-link-system-path="node/12373">Science Seminars and Data Skills Webinars</a>

                  </li>
              <li  class="menu__item">
          <a href="https://data.neonscience.org/documents" class="menu__link" data-plugin-id="menu-link-content1ffb7d51-3f5f-4272-9d13-9c693401801d">Document Library</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/resources/funding-opportunities" class="menu__link" data-plugin-id="menu-link-content67efc63f-fc52-491c-a9be-4b1c66225712" data-drupal-link-system-path="node/11327">Funding Opportunities </a>

                  </li>
      
          </ul>
  

                          </div>

              <div class='subNavLabelWrapper'>
                <button class='subNavClose isDesktop' aria-label='Close Mega Menu'>
                  <svg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M.808 17.778L17.778.808l1.414 1.414-16.97 16.97-1.414-1.414z' fill='#fff'/><path d='M2.222.808l16.97 16.97-1.414 1.414L.808 2.222 2.222.808z' fill='#fff'/></svg>
                </button>
                <h3 class='isDesktop'>Resources</h3>
                <button class='mobileBack isMobile' aria-label='Back'>
                  <svg width='14' height='10' viewBox='0 0 14 10' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M5 9L1 5l4-4M1 5h12' stroke='#0073CF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                  </svg>
                </button>
              </div>
                            </div>

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/impact" title="Impact" class="menu__link" data-plugin-id="menu-link-content4878b38c-97fc-4a8b-9477-8e4ee7737dcc" data-drupal-link-system-path="node/8795">Impact</a>

          
                                        <button class='arrow visually-hidden focusable' tabindex='0' aria-label='Toggle Mega Menu'><svg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M9 1L5 5 1 1' stroke='#fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></button>

                            <div class='subNavWrapper'>
                            <div class='innerSubNavWrapper'>
            
                      <ul  class="menu menu--main" data-depth="1">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/impact/research-highlight-blog" class="menu__link" data-plugin-id="menu-link-content11c453ba-42f9-4e06-b17e-f57eb98caa04" data-drupal-link-system-path="node/10904">Research Highlights</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/impact/papers-publications" class="menu__link" data-plugin-id="menu-link-contente7892e9f-e782-4116-8668-13b1ff946130" data-drupal-link-system-path="node/10915">Papers &amp; Publications</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/impact/neon-news" class="menu__link" data-plugin-id="menu-link-content5781483e-2e3a-413b-8c02-0a5e6c88096a" data-drupal-link-system-path="node/6042">NEON in the News</a>

                  </li>
      
          </ul>
  

                          </div>

              <div class='subNavLabelWrapper'>
                <button class='subNavClose isDesktop' aria-label='Close Mega Menu'>
                  <svg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M.808 17.778L17.778.808l1.414 1.414-16.97 16.97-1.414-1.414z' fill='#fff'/><path d='M2.222.808l16.97 16.97-1.414 1.414L.808 2.222 2.222.808z' fill='#fff'/></svg>
                </button>
                <h3 class='isDesktop'>Impact</h3>
                <button class='mobileBack isMobile' aria-label='Back'>
                  <svg width='14' height='10' viewBox='0 0 14 10' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M5 9L1 5l4-4M1 5h12' stroke='#0073CF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                  </svg>
                </button>
              </div>
                            </div>

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/get-involved" class="menu__link" data-plugin-id="menu-link-contentd5bd0f4f-d783-4405-b899-1e7680f1f88f" data-drupal-link-system-path="node/6753">Get Involved</a>

          
                                        <button class='arrow visually-hidden focusable' tabindex='0' aria-label='Toggle Mega Menu'><svg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M9 1L5 5 1 1' stroke='#fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></button>

                            <div class='subNavWrapper'>
                            <div class='innerSubNavWrapper'>
            
                      <ul  class="menu menu--main" data-depth="1">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/get-involved/upcoming-events" class="menu__link" data-plugin-id="menu-link-contente2867599-4e9d-41b9-8c7f-126d3a302d0b" data-drupal-link-system-path="node/10737">Upcoming Events</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/get-involved/past-events" class="menu__link" data-plugin-id="menu-link-contentca98b8b9-9aee-474c-ae16-2e049615c72d" data-drupal-link-system-path="node/15106">Past Events</a>

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/research-and-collaborations" class="menu__link" data-plugin-id="menu-link-content7ad2b09c-c124-4a91-84b1-4b2ff93bd594" data-drupal-link-system-path="node/12059">Research and Collaborations</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/environmental-data-science-innovation-and-impact-lab" class="menu__link" data-plugin-id="menu-link-contenta0404736-a121-4da1-9e2b-6b29189a32a4" data-drupal-link-system-path="node/13683"> Environmental Data Science Innovation and Inclusion Lab</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/collaboration-doe-ber-user-facilities-and-programs" class="menu__link" data-plugin-id="menu-link-content0935ec96-6ac5-4e5c-8352-6af4e6678e90" data-drupal-link-system-path="node/13669">Collaboration with DOE BER User Facilities and Programs</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/efi-rcn-neon-ecological-forecasting-challenge" class="menu__link" data-plugin-id="menu-link-contentbece2b5c-bfc9-474f-b5f0-dc4d64a5bbf4" data-drupal-link-system-path="node/11363">EFI-NEON Ecological Forecasting Challenge</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/neon-great-lakes-user-group" class="menu__link" data-plugin-id="menu-link-content4c1299dd-fb77-413b-bb3d-547392ad3d6c" data-drupal-link-system-path="node/12371">NEON Great Lakes User Group</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/ncar-neon-community-collaborations" class="menu__link" data-plugin-id="menu-link-content2bc3207d-fc3c-4d83-a352-5842f74b01bf" data-drupal-link-system-path="node/11843">NCAR-NEON-Community Collaborations</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/research-and-collaborations/sage-grande" class="menu__link" data-plugin-id="menu-link-content373af4ad-7ddf-4b77-857b-b8845ad7c062" data-drupal-link-system-path="node/15210">Sage Grande Testbed</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/about/advisory-groups" class="menu__link" data-plugin-id="menu-link-content05c66823-e082-4444-a52e-dfba3e2a4396" data-drupal-link-system-path="node/10534">Advisory Groups</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/advisory-groups/steac" class="menu__link" data-plugin-id="menu-link-content2805ed89-40f1-468a-8114-e1ea19b4d0c6" data-drupal-link-system-path="node/7563">Science, Technology &amp; Education Advisory Committee (STEAC)</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/advisory-groups/iac" class="menu__link" data-plugin-id="menu-link-contentedf8b2f7-64ad-4962-b228-caa49bc70c03" data-drupal-link-system-path="node/14802">Innovation Advisory Committee (IAC)</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/about/advisory-groups/twgs" class="menu__link" data-plugin-id="menu-link-content8badae4b-878e-4ed6-9369-27630c59049d" data-drupal-link-system-path="node/6273">Technical Working Groups (TWG)</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item menu__item--expanded">
          <a href="https://www.neonscience.org/neon-ambassador-program" class="menu__link" data-plugin-id="menu-link-contentc7e5c1a7-f0e7-440e-a410-4aaca896b64b" data-drupal-link-system-path="node/11625">NEON Ambassador Program</a>

          
            
                      <ul  class="menu menu--main" data-depth="2">

              <li  class="menu__item">
          <a href="https://www.neonscience.org/workshop-series-exploring-neon-derived-data-products" class="menu__link" data-plugin-id="menu-link-contentf23464a6-0005-45c9-a8e8-65e44fd1f580" data-drupal-link-system-path="node/12804">Exploring NEON-Derived Data Products Workshop Series</a>

                  </li>
      
          </ul>
  

            

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/get-involved/partners" class="menu__link" data-plugin-id="menu-link-contentbbf150a7-d1e4-4cfa-a8b0-847989c52745" data-drupal-link-system-path="node/8094">Partnerships</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/get-involved/engagement" class="menu__link" data-plugin-id="menu-link-content32ac66ec-e6bb-4590-ad08-5c16f174f3b8" data-drupal-link-system-path="node/10556">Community Engagement</a>

                  </li>
              <li  class="menu__item">
          <a href="https://www.neonscience.org/get-involved/work-opportunities" class="menu__link" data-plugin-id="menu-link-content01967027-b778-4bf5-ae89-7f0616c6ef68" data-drupal-link-system-path="node/6229">Work Opportunities</a>

                  </li>
      
          </ul>
  

                          </div>

              <div class='subNavLabelWrapper'>
                <button class='subNavClose isDesktop' aria-label='Close Mega Menu'>
                  <svg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M.808 17.778L17.778.808l1.414 1.414-16.97 16.97-1.414-1.414z' fill='#fff'/><path d='M2.222.808l16.97 16.97-1.414 1.414L.808 2.222 2.222.808z' fill='#fff'/></svg>
                </button>
                <h3 class='isDesktop'>Get Involved</h3>
                <button class='mobileBack isMobile' aria-label='Back'>
                  <svg width='14' height='10' viewBox='0 0 14 10' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M5 9L1 5l4-4M1 5h12' stroke='#0073CF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                  </svg>
                </button>
              </div>
                            </div>

            

                  </li>
      
                      <li class="myAccount">
          <a href="/user/login" title="My Account" class="menu__link">My Account</a>
        </li>

                <li class="siteSearch isDesktop">
          <a href="/search/site" title="Search" class="menu__link">Search</a>
        </li>
                <li class="siteSearch isMobile">
          
<div
  class="search-form-mobile isMobile"
  id="block-searchform-mobile"
  role="search"
>
  

<div class="search-api-form" data-drupal-selector="search-api-form" id="block-searchform">
  
    
      <form action="/search/site" method="get" id="search-api-form" accept-charset="UTF-8">
  <div class="js-form-item form-item js-form-type-search form-item-title js-form-item-title form-no-label">
        <input title="Enter the terms you wish to search for." data-drupal-selector="edit-title" type="search" id="edit-title" name="title" value="" size="15" maxlength="128" placeholder="" class="form-search" />

        <label for="edit-title" class="visually-hidden">Search</label>
      </div>
<div data-drupal-selector="edit-actions" class="form-actions js-form-wrapper form-wrapper" id="edit-actions">

<input data-drupal-selector="edit-submit" type="submit" id="edit-submit" value="Search" class="button js-form-submit form-submit button--search" />
</div>

</form>

  </div>

</div>
        </li>
          </ul>
  


  </nav>

      </div>
    </div>
  </div>
  <div class="l--offset isDesktop header__search visually-hidden">
    <div class="header__search--inner">
        <h3 class="header__search--title">Search</h3>
        

<div class="search-api-form" data-drupal-selector="search-api-form" id="block-searchform">
  
    
      <form action="/search/site" method="get" id="search-api-form" accept-charset="UTF-8">
  <div class="js-form-item form-item js-form-type-search form-item-title js-form-item-title form-no-label">
        <input title="Enter the terms you wish to search for." data-drupal-selector="edit-title" type="search" id="edit-title" name="title" value="" size="15" maxlength="128" placeholder="" class="form-search" />

        <label for="edit-title" class="visually-hidden">Search</label>
      </div>
<div data-drupal-selector="edit-actions" class="form-actions js-form-wrapper form-wrapper" id="edit-actions">

<input data-drupal-selector="edit-submit" type="submit" id="edit-submit" value="Search" class="button js-form-submit form-submit button--search" />
</div>

</form>

  </div>

        <div class="header__search-close">
          <button aria-label="Close Search" class="button__search-close">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M.808 17.778L17.778.808l1.414 1.414-16.97 16.97-1.414-1.414z" fill="#0073CF"/><path d="M2.222.808l16.97 16.97-1.414 1.414L.808 2.222 2.222.808z" fill="#0073CF"/></svg>
          </button>

        </div>
    </div>
  </div>
          <div id="header__authentication-ui"></div>
  </header>



`;e.i(32073),e.i(36729);var eR=e.i(55199);e.i(1168);var eD=e.i(5169),eI=e.i(36222),eP=e.i(55534),ez=e.i(5248);(0,ez.default)((0,v.jsx)("path",{d:"m17 7-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4z"}),"Logout");var eF=e.i(33987),eH=e.i(55609);e.i(42570);var eB=e.i(46394),e$=e.i(70774),eU=((t={}).REDIRECT="REDIRECT",t.SILENT="SILENT",t),eW=((a={}).MENU="MENU",a.MENU_CUSTOM="MENU_CUSTOM",a);let eV=(0,eB.makeStyles)()(e=>({button:{whiteSpace:"nowrap",color:`${e.palette.primary.main} !important`,textTransform:"uppercase !important",fontSize:"0.55rem !important",fontWeight:"600 !important",fontFamily:'"Inter",Helvetica,Arial,sans-serif !important',lineHeight:"1.75 !important"},loadingContainer:{display:"flex",width:"110px",justifyContent:"center",alignItems:"center",margin:e.spacing(.5),"& span.MuiCircularProgress-root":{marginRight:"5px"}},loadingContainerCustom:{display:"flex",width:"64px",justifyContent:"center",alignItems:"center",margin:e.spacing(.5)},loadingContainerSpan:{marginRight:e.spacing(1),color:e.palette.grey[400],fontSize:`${eE.FONT_SIZES["12"].rem}rem`},accountMenuContainer:{"& :focus":{outline:"none !important"}}})),eG=eH.default.getWrappedComponent(e=>{let[{auth:{isAuthenticated:t,isAuthWorking:a,isAuthWsConnected:n},fetches:{auth:{status:s}}},l]=eH.default.useNeonAuthContextState(),{classes:o}=eV(),d=[eH.FETCH_STATUS.AWAITING_CALL,eH.FETCH_STATUS.FETCHING].includes(s),c=[eH.FETCH_STATUS.SUCCESS,eH.FETCH_STATUS.ERROR].indexOf(s)>=0,u=(0,r.useCallback)(()=>{eF.default.fetchUserInfoWithDispatch(l)},[l]);return(0,r.useEffect)(()=>{d||c||ej.default.auth0DisableApi||u()},[u,d,c]),(0,i.jsx)(i.Fragment,{})});var eq=e.i(71944),eY=e.i(45281),eX=e.i(94059),eK=e.i(92481),eZ=e.i(17206),eJ=e.i(32020);let eQ=eT.DRUPAL_HEADER_HTML.KEY,e0=eT.DRUPAL_FOOTER_HTML.KEY,e1="AWAITING_CALL",e2="FETCHING",e4="ERROR",e3="SUCCESS",e7={html:{[eQ]:null,[e0]:null},fetches:{[eQ]:{status:null,error:null},[e0]:{status:null,error:null}},isActive:!1,isFinal:!1,hasError:!1,whenFinalCalled:!1},e5=(0,r.createContext)(e7),e8=(e,t)=>{let a={...e,fetches:(0,eq.default)(e.fetches)},n=t.asset&&Object.keys(eT).includes(t.asset);switch(t.type){case"fetchCalled":if(!t.key||!e.fetches[t.key])return e;return a.fetches[t.key].status=e2,a;case"fetchHtmlSucceeded":if(!n)return e;return a.fetches[t.asset].status=e3,a.html[t.asset]=t.html,a;case"fetchHtmlFailed":if(!n)return e;return a.fetches[t.asset].status=e4,a.fetches[t.asset].error=t.error,a;case"whenFinalCalled":return{...a,whenFinalCalled:!0};default:return e}},e6={children:null,fetchPartials:!1,whenFinal:()=>{}},e9=e=>{let{children:t,fetchPartials:a,whenFinal:n}=(0,eJ.resolveProps)(e6,e),s=(0,eq.default)(e7);s.isActive=!0,a&&(s.fetches[eQ].status=e1,s.fetches[e0].status=e1);let[l,o]=(0,r.useReducer)(e8,s),{isFinal:d,whenFinalCalled:c}=l,u=e=>{if(!Object.keys(eT).includes(e))return;let{url:t}=eT[e];(0,eZ.ajax)({url:t,method:"GET",responseType:"text"}).pipe((0,eX.map)(t=>(o({type:"fetchHtmlSucceeded",asset:e,html:t.response.replace(/ value=""/g,' initialValue=""')}),(0,eY.of)(!0))),(0,eK.catchError)(t=>(o({type:"fetchHtmlFailed",asset:e,error:t}),(0,eY.of)(!1)))).subscribe()},p={[eQ]:()=>u(eQ),[e0]:()=>u(e0)};return(0,r.useEffect)(()=>{Object.keys(l.fetches).forEach(e=>{l.fetches[e].status===e1&&"function"==typeof p[e]&&(o({type:"fetchCalled",key:e}),p[e]())})}),(0,r.useEffect)(()=>{d&&!c&&(n((0,eq.default)({...l,whenFinalCalled:!0})),o({type:"whenFinalCalled"}))},[d,c,n,l]),(0,i.jsx)(e5.Provider,{value:[l,o],children:t})},te={children:s.default.oneOfType([s.default.arrayOf(s.default.oneOfType([s.default.node,s.default.string])),s.default.node,s.default.string]),fetchPartials:s.default.bool,whenFinal:s.default.func};e9.propTypes=te;let tt=()=>{let e=(0,r.useContext)(e5);return Array.isArray(e)&&e[0].isActive?e:[{...e7},()=>{}]};var ta=e.i(97380);let tn=(0,ez.default)((0,v.jsx)("path",{d:"M4 8h4V4H4zm6 12h4v-4h-4zm-6 0h4v-4H4zm0-6h4v-4H4zm6 0h4v-4h-4zm6-10v4h4V4zm-6 4h4V4h-4zm6 6h4v-4h-4zm0 6h4v-4h-4z"}),"Apps");var ti=e.i(12647);let tr=(0,ez.default)((0,v.jsx)("path",{d:"M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3z"}),"Launch");var ts=e.i(21435),tl=e.i(17050),to=e.i(86157);let td=(0,eB.makeStyles)()(e=>({menuContainer:{zIndex:1e3},toolbarContainer:{display:"flex",justifyContent:"space-between",boxShadow:"0px 1px 3px rgba(0, 0, 0, 0.45)",position:"relative"},toolbarButtons:{display:"flex",marginLeft:"auto",marginRight:e.spacing(2.5),marginTop:e.spacing(.5),marginBottom:e.spacing(.5)},paper:{padding:e.spacing(4),maxWidth:"500px",marginTop:e.spacing(1),overflowX:"unset",overflowY:"unset","&::before":{content:'""',position:"absolute",marginRight:e.spacing(4),top:0,right:0,width:e.spacing(2),height:e.spacing(2),backgroundColor:e.palette.background.paper,boxShadow:e.shadows[2],transform:"rotate(315deg)",clipPath:"polygon(-5px -5px, calc(100% + 5px) -5px, calc(100% + 5px) calc(100% + 5px))"}},card:{transition:"0.4s","&:hover":{transform:"translateY(-2px)",boxShadow:e.shadows[2]},cursor:"pointer",width:"100%",border:0},cardContent:{textAlign:"center"},gridItem:{display:"flex"}})),tc=e=>{let{apps:t}=e,{classes:a}=td(),[n,s]=(0,r.useState)(!1),l=(0,r.useRef)(null),[o,d]=(0,r.useState)(null),c=e=>{l.current&&l.current.contains(e.target)||s(!1)},u=(0,r.useCallback)(e=>{window.location.href=e},[]);return(0,i.jsx)("div",{className:a.toolbarContainer,children:(0,i.jsxs)("div",{className:a.toolbarButtons,children:[(0,i.jsx)(eP.default,{title:"Neon Applications","aria-label":"Neon Applications",placement:"left",slots:{transition:ti.default},slotProps:{transition:{timeout:200}},arrow:!0,children:(0,i.jsx)(z.default,{ref:l,style:{color:"black"},"aria-label":"more","aria-controls":n?"neon-application-menu":void 0,"aria-haspopup":"true",onClick:()=>{d(l.current),s(e=>!e)},onKeyDown:e=>{"Tab"===e.key&&(e.preventDefault(),s(!1))},size:"large",children:(0,i.jsx)(tn,{})})}),(0,i.jsx)(ta.default,{className:a.menuContainer,open:n,anchorEl:o,role:"presentation",transition:!0,children:({TransitionProps:e,placement:n})=>(0,i.jsx)(ti.default,{...e,style:{transformOrigin:"bottom"===n?"center top":"center bottom"},timeout:200,children:(0,i.jsx)(H.default,{elevation:3,className:a.paper,children:(0,i.jsx)(eh.default,{onClickAway:c,children:(0,i.jsx)(to.default,{container:!0,spacing:4,sx:{alignItems:"stretch"},children:t.map(e=>(0,i.jsx)(to.default,{size:{xs:1===t.length?12:6},className:a.gridItem,children:(0,i.jsx)(ts.default,{onClick:t=>u(e.url),className:a.card,children:(0,i.jsxs)(tl.default,{className:a.cardContent,children:[(0,i.jsx)(tr,{fontSize:"large"}),(0,i.jsx)(g.default,{variant:"subtitle1",gutterBottom:!0,style:{lineHeight:1},children:e.name}),e.description]})},e.url)},e.name))})})})})})]})})},tu=()=>{let[{auth:e}]=eH.default.useNeonAuthContextState(),t=e?.userData?.data?.apps;return t?.length>0?(0,i.jsx)(tc,{apps:t}):null},tp={src:e.i(34005).default,width:20,height:20,blurWidth:0,blurHeight:0},th={src:e.i(19240).default,width:20,height:20,blurWidth:0,blurHeight:0},tm=eT.DRUPAL_HEADER_HTML.KEY,tf="header__authentication-ui",tg={drupalCssLoaded:!1,unstickyDrupalHeader:!0,showSkeleton:!1,customizeAuthContainer:!1},tb=t=>{let a,n,{ref:s,drupalCssLoaded:l,unstickyDrupalHeader:o,showSkeleton:c,customizeAuthContainer:u}=(0,eJ.resolveProps)(tg,t),{classes:p,theme:h}=(a=u?{}:{"& button:first-of-type":{borderRight:"none",borderTopLeftRadius:"0px",borderBottomRightRadius:"0px"}},n=u?{"& .header__inner.l--offset-wide":{marginRight:"120px !important",paddingRight:"0px !important",maxWidth:"unset !important"},"& .header__inner.l--offset-wide > .authContainer":{display:"none !important"},"& .header__inner > .header__site-navigation > .header__menu-main > nav > ul.menu--main":{"& > li.menu__item":{marginRight:"42px !important"},"& > li.siteSearch":{marginLeft:"-10px !important"}}}:{"& .header__inner.l--offset-wide":{paddingLeft:"calc(2/27*100%)",paddingRight:"calc(2/27*100%)"},"& .header__inner.l--offset-wide > .authContainer":{display:"none !important"}},(0,eB.makeStyles)()(e=>({skeletonHeader:{boxShadow:"0px 1px 3px rgba(0, 0, 0, 0.25), 0px 1px 1px rgba(0, 0, 0, 0.25)"},coreAuthContainer:{textAlign:"right",position:"absolute",zIndex:15,[e.breakpoints.up("lg")]:{padding:"0px",top:u?"47px":"-1px",right:u?"32px":"0px",...a},[e.breakpoints.down("lg")]:{padding:u?e.spacing(1,1.5):e.spacing(1,2),top:e.spacing(1),right:e.spacing(9)}},unstickyHeader:{paddingTop:"unset !important","& > header":{position:"unset !important",'& label[for="nav-trigger"]':{zIndex:"9 !important"},"& div.header__site-navigation":{zIndex:"8 !important"},[e.breakpoints.down("md")]:{"& .header__site-navigation":{display:"none"},"& .nav-trigger:checked ~ .header__site-navigation":{display:"block"}}}},headerContainerFallback:{"& li.siteSearch > a":{background:`url('${tp.src}') center center no-repeat !important`},"& li.siteSearch > a:hover":{background:`url('${th.src}') center center no-repeat !important`}},headerContainer:{...n,"& .header__search":{background:"#f5f6f7",position:"relative",zIndex:1,transition:"all 0.2s ease-in-out",opacity:1,visibility:"visible",fontSize:"1.1rem"},"& .header__search.visually-hidden":{visibility:"hidden",opacity:0,transition:"all 0.2s ease-in-out"},"& .header__search > .header__search--inner":{display:"flex",maxWidth:"1620px",margin:"0 auto"},"& .header__search--inner":{display:"flex",msFlexPack:"start",justifyContent:"flex-start",msFlexAlign:"center",alignItems:"center"},"& .header__search--inner > .header__search--title":{fontWeight:"600 !important",fontSize:"0.9rem !important",margin:"0 2.6rem 0 0 !important"},[e.breakpoints.up("lg")]:{"& .header__search--inner > .header__search--title":{fontSize:"1.25rem !important"}},"& .header__search--inner > div.search-api-form > form#search-api-form":{display:"flex",msFlexPack:"start",justifyContent:"flex-start",msFlexAlign:"center",alignItems:"center",width:"100%"},"& .header__search--inner > div.search-api-form > form#search-api-form > .form-item":{width:"100%",display:"flex",msFlexPack:"start",justifyContent:"flex-start",msFlexAlign:"center",alignItems:"center",margin:"20px 0"},"& .header__search--inner > div.search-api-form":{width:"100%"},"& .header__search--inner > div.search-api-form > form#search-api-form > .form-actions.form-wrapper":{display:"flex"},"& .header__search--inner > div.search-api-form > form#search-api-form > .form-item > .form-search":{width:"100%",height:"48px",background:"#fff",border:"1px solid #d7d9d9",boxSizing:"border-box",borderRadius:"3px 0 0 3px",padding:"12px 18px"},"& .header__search--inner > div.search-api-form > form#search-api-form > .form-actions > .form-submit.button--search":{borderRadius:"0px 3px 3px 0px",width:"48px",height:"48px",background:"#0073cf",border:"#0073cf 1px solid",color:"transparent !important",backgroundImage:'url("data:image/svg+xml,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20clip-rule%3D%22evenodd%22%20d%3D%22M8%202a6%206%200%20100%2012A6%206%200%20008%202zM0%208a8%208%200%201114.32%204.906l5.387%205.387-1.414%201.414-5.387-5.387A8%208%200%20010%208z%22%20fill%3D%22%23fff%22%2F%3E%3C%2Fsvg%3E")',backgroundPosition:"center center",backgroundRepeat:"no-repeat",padding:"0.86rem 0",transition:"all 0.2s ease-in-out"},"& .header__search--inner > div.search-api-form > form#search-api-form > .form-actions > .form-submit.button--search:hover":{transition:"all 0.2s ease-in-out",backgroundColor:"#002c77",border:"#002c77 1px solid"},"& .header__search--inner > div.search-api-form > form#search-api-form > .form-item > label":{fontWeight:600,fontSize:"1.25rem",margin:"auto 2.6rem"},"& .header__search--inner > .header__search-close":{margin:"auto 2.5rem"},"& .header__search--inner > .header__search-close > button":{background:"none",border:"none",width:"50px"},"& .header__search--inner > .header__search-close > button > svg":{verticalAlign:"middle"},"& .header__search--inner > .header__search-close > button > svg > path":{transition:"all 0.2s ease-in-out"},"& .header__search--inner > .header__search-close > button:hover > svg > path":{fill:"#002c77",transition:"all 0.2s ease-in-out"},[e.breakpoints.down("lg")]:{"& nav#block-neon-main-menu > ul.menu.menu--main > li.siteSearch":{padding:"0 1.875rem 0.625rem 1.875rem !important"},"& .search-form-mobile.isMobile":{display:"flex",msFlexPack:"start",justifyContent:"flex-start",msFlexAlign:"center",alignItems:"center"},"& .search-form-mobile":{width:"100%"},"& .search-form-mobile > div.search-api-form > form#search-api-form":{display:"flex",msFlexPack:"start",justifyContent:"flex-start",msFlexAlign:"center",alignItems:"center",width:"100%"},"& .search-form-mobile > div.search-api-form":{width:"100%"},"& .search-form-mobile > div.search-api-form > form#search-api-form > .form-item":{width:"100%",margin:"20px 0"},"& .search-form-mobile > div.search-api-form > form#search-api-form > .form-item > .form-search":{width:"100%",height:"48px",background:"#fff",border:"1px solid #d7d9d9",boxSizing:"border-box",borderRadius:"3px 0 0 3px",padding:"0.75rem 1.125rem"},"& .search-form-mobile > div.search-api-form > form#search-api-form > .form-actions.form-wrapper":{display:"flex"},"& .search-form-mobile > div.search-api-form > form#search-api-form > .form-actions > .form-submit.button--search":{borderRadius:"0px 3px 3px 0px",width:"48px",height:"48px",background:"#0073cf",border:"#0073cf 1px solid",color:"transparent !important",backgroundImage:'url("data:image/svg+xml,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20clip-rule%3D%22evenodd%22%20d%3D%22M8%202a6%206%200%20100%2012A6%206%200%20008%202zM0%208a8%208%200%201114.32%204.906l5.387%205.387-1.414%201.414-5.387-5.387A8%208%200%20010%208z%22%20fill%3D%22%23fff%22%2F%3E%3C%2Fsvg%3E")',backgroundPosition:"center center",backgroundRepeat:"no-repeat",padding:"14px 0",transition:"all 0.2s ease-in-out"},"& .search-form-mobile > div.search-api-form > form#search-api-form > .form-actions > .form-submit.button--search:hover":{transition:"all 0.2s ease-in-out",backgroundColor:"#002c77",border:"#002c77 1px solid"},"& .search-form-mobile > div.search-api-form > form#search-api-form > .form-item > label":{display:"none !important"}}}})))(),m=(0,d.default)(h.breakpoints.down("lg")),[{isActive:f,fetches:{[tm]:g},html:{[tm]:b}}]=tt(),[{isActive:w,auth:x}]=eH.default.useNeonAuthContextState(),[v,k]=(0,r.useState)(e1),[y,_]=(0,r.useState)(!1),C="loading";if(f)switch(g.status){case e3:C=b&&l&&y?"drupal":"loading";break;case e4:C=l?"drupal-fallback":"loading";break;default:C=ej.default.fetchDrupalAssets?"loading":"drupal-fallback"}(0,r.useLayoutEffect)(()=>{if(!ej.default.fetchDrupalAssets)return void(async()=>{await e.A(16553),k(e3)})();if(!C.includes("drupal")||v!==e1||!y||!l)return;k(e2);let t=document.createElement("script");t.src=eT.DRUPAL_HEADER_JS.url,t.onload=()=>{k(e3)},t.onerror=()=>{(async()=>{t.remove(),await e.A(16553),k(e3)})()},document.body.appendChild(t)},[v,l,y,k,C]);let S=b;switch(C){case"drupal":S=b;break;case"drupal-fallback":S=eM}if((0,r.useLayoutEffect)(()=>{if(f&&S&&l&&!y){let e=window.setTimeout(()=>_(!0),0);return()=>window.clearTimeout(e)}return()=>{}},[f,S,l,y]),"loading"===C&&c)return(0,i.jsx)("header",{ref:s,id:"header",className:p.skeletonHeader,children:(0,i.jsx)(eo,{variant:"rectangular",height:`${m?60:120}px`,width:"100%"})});let j=x.useCore&&w?{replace:e=>{let{attribs:t={},name:a}=e;if("form"===a&&"search-api-form"===t.id){var n;let a,r={};return Object.keys(t).forEach(e=>{"class"===e?r.className=t[e]:e.includes("-")?r[(0,eL.default)(e)]=t[e]:r[e]=t[e]}),(0,i.jsx)("form",{...r,action:(n=t.action,a=ej.default.getWebHost(),n?n.startsWith("/")?`${a}${n}`:`${a}/${n}`:`${a}/search/site`),children:(0,eA.domToReact)(e.children,j)})}return t.id!==tf?null:(0,i.jsx)("div",{id:tf,className:p.coreAuthContainer,children:(0,i.jsx)(eG,{loginPath:ej.default.getFullAuthPath("login"),logoutPath:ej.default.getFullAuthPath("logout"),accountPath:ej.default.route.buildAccountRoute(),loginType:eU.REDIRECT,logoutType:eU.SILENT,displayType:u?eW.MENU_CUSTOM:eW.MENU})})}}:void 0,T=p.headerContainer;o&&(T=`${p.unstickyHeader} ${p.headerContainer}`),"drupal-fallback"!==C&&("loading"!==C||c)||(T=`${T} ${p.headerContainerFallback}`);let E="drupal"===C?b:eM;return(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)("header",{ref:s,id:"header",className:T,children:eO(E,j)}),(0,i.jsx)(tu,{})]})};tb.propTypes={ref:s.default.element,drupalCssLoaded:s.default.bool,unstickyDrupalHeader:s.default.bool,showSkeleton:s.default.bool,customizeAuthContainer:s.default.bool};let tw=`
<footer class="footer-top__wrapper" role="contentinfo">
  <div class="footer-top__inner l--offset-wide l--wrapper">
  <div class="footer-top__logo-social">
    <div class="footer-top__logo">
      <img height="60" width="167" src="https://www.neonscience.org/themes/custom/neon/logo-white.svg" alt="" />
    </div>
    <div class="footer-top__social">
      <h4>Follow Us:</h4>
      <ul>
        <li class="facebook"><a href="https://www.facebook.com/NEONScienceData" target="_blank" rel="noopener noreferrer" aria-label="Facebook link"><svg viewBox="0 0 12 24" width="12" height="24" xmlns="http://www.w3.org/2000/svg"><path d="M3 24V13H0V9h3V5.5C3 2.667 4.778 0 9 0c1.694 0 3 .222 3 .222L11.833 4H9.25C7.722 4 7.5 4.833 7.5 6v3H12l-.306 4H7.5v11H3z" fill="inherit" fill-rule="nonzero"/></svg></a></li>
        <li class="twitter"><a href="https://x.com/NEON_Sci" target="_blank" rel="noopener noreferrer" aria-label="X link"><svg xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 512 462.799"><path fill-rule="nonzero" d="M403.229 0h78.506L310.219 196.04 512 462.799H354.002L230.261 301.007 88.669 462.799h-78.56l183.455-209.683L0 0h161.999l111.856 147.88L403.229 0zm-27.556 415.805h43.505L138.363 44.527h-46.68l283.99 371.278z"/></svg></a></li>
        <li class="linkedin"><a href="https://www.linkedin.com/company/neon-science" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn link"><svg viewBox="0 0 24 24"  width="24" height="24" xmlns="http://www.w3.org/2000/svg"><path d="M.5 8.5h5V24h-5V8.5zm13 6.5v9h-5V12s-.083-2.972-.083-3.5h4.916l.167 2.139C14.5 9.139 16 8 18 8c3.5 0 6 2.5 6 7v9h-5v-8.5c0-2.5-1.222-3.5-2.722-3.5S13.5 13 13.5 15zM2.972 6h-.028C1.167 6 0 4.778 0 3.25 0 1.694 1.194.5 3.028.5 4.833.5 5.972 1.694 6 3.25 6 4.778 4.833 6 2.972 6z" fill="inherit" fill-rule="nonzero"/></svg></a></li>
        <li class="youtube"><a href="http://www.youtube.com/neonscience" target="_blank" rel="noopener noreferrer" aria-label="YouTube link"><svg viewBox="0 0 24 18"  width="24" height="18" xmlns="http://www.w3.org/2000/svg"><path d="M12 .5c12 0 12 0 12 8.5s0 8.5-12 8.5S0 17.5 0 9 0 .5 12 .5zm-3 13L16.5 9 9 4.5v9z" fill="inherit" fill-rule="evenodd"/></svg></a></li>
        <li class="instgram"><a href="https://www.instagram.com/neon.sci/" target="_blank" rel="noopener noreferrer" aria-label="Instagram link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="18"><path d="M17.34,5.46h0a1.2,1.2,0,1,0,1.2,1.2A1.2,1.2,0,0,0,17.34,5.46Zm4.6,2.42a7.59,7.59,0,0,0-.46-2.43,4.94,4.94,0,0,0-1.16-1.77,4.7,4.7,0,0,0-1.77-1.15,7.3,7.3,0,0,0-2.43-.47C15.06,2,14.72,2,12,2s-3.06,0-4.12.06a7.3,7.3,0,0,0-2.43.47A4.78,4.78,0,0,0,3.68,3.68,4.7,4.7,0,0,0,2.53,5.45a7.3,7.3,0,0,0-.47,2.43C2,8.94,2,9.28,2,12s0,3.06.06,4.12a7.3,7.3,0,0,0,.47,2.43,4.7,4.7,0,0,0,1.15,1.77,4.78,4.78,0,0,0,1.77,1.15,7.3,7.3,0,0,0,2.43.47C8.94,22,9.28,22,12,22s3.06,0,4.12-.06a7.3,7.3,0,0,0,2.43-.47,4.7,4.7,0,0,0,1.77-1.15,4.85,4.85,0,0,0,1.16-1.77,7.59,7.59,0,0,0,.46-2.43c0-1.06.06-1.4.06-4.12S22,8.94,21.94,7.88ZM20.14,16a5.61,5.61,0,0,1-.34,1.86,3.06,3.06,0,0,1-.75,1.15,3.19,3.19,0,0,1-1.15.75,5.61,5.61,0,0,1-1.86.34c-1,.05-1.37.06-4,.06s-3,0-4-.06A5.73,5.73,0,0,1,6.1,19.8,3.27,3.27,0,0,1,5,19.05a3,3,0,0,1-.74-1.15A5.54,5.54,0,0,1,3.86,16c0-1-.06-1.37-.06-4s0-3,.06-4A5.54,5.54,0,0,1,4.21,6.1,3,3,0,0,1,5,5,3.14,3.14,0,0,1,6.1,4.2,5.73,5.73,0,0,1,8,3.86c1,0,1.37-.06,4-.06s3,0,4,.06a5.61,5.61,0,0,1,1.86.34A3.06,3.06,0,0,1,19.05,5,3.06,3.06,0,0,1,19.8,6.1,5.61,5.61,0,0,1,20.14,8c.05,1,.06,1.37.06,4S20.19,15,20.14,16ZM12,6.87A5.13,5.13,0,1,0,17.14,12,5.12,5.12,0,0,0,12,6.87Zm0,8.46A3.33,3.33,0,1,1,15.33,12,3.33,3.33,0,0,1,12,15.33Z"/></svg></a></li>
      </ul>
    </div>

    </div>


    <div class="footer-top__newsletter">
      <div class="footer-top__newsletter-text">
        <h4>Join Our Newsletter</h4>
        <p>Get updates on events, opportunities, and how NEON is being used today.</p>
      </div>
      <div class="footer-top__newsletter-btn">
        <a href="https://www.neonscience.org/neon-newsletter-sign">Subscribe Now
        <svg width="13px" height="10px" viewBox="0 0 13 10" xmlns="http://www.w3.org/2000/svg"><g class="chevronGroup" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round" stroke="#FFFFFF" stroke-width="2" transform="translate(1.000000, 1.000000)"><path d="M11,4 L7,8" class="bottom"></path><path d="M11,4 L7,0" class="top"></path><path d="M11,4 L0,4" class="line"></path></g></svg>
                </a>
      </div>
    </div>


      </div>
</footer>

<footer class="footer-bottom__wrapper" role="contentinfo">
  <div class="footer-bottom__inner l--offset-wide l--wrapper">
    <div class="footer-bottom__menu">
        <div>
    <nav role="navigation" aria-labelledby="block-footer-menu" id="block-footer">
            
  <h2 class="visually-hidden" id="block-footer-menu">Footer</h2>
  

        
          <ul  class="menu menu--footer" data-depth="0">
          <li  class="menu__item">
        <a href="https://www.neonscience.org/about" title="About Us" class="menu__link" data-plugin-id="menu-link-content06cc4ae6-95bb-4346-a0e9-6f27f3c48368" data-drupal-link-system-path="node/8793">About Us</a>
              </li>
          <li  class="menu__item">
        <a href="https://www.neonscience.org/about/contact-us" title="Contact Us" class="menu__link" data-plugin-id="menu-link-contentdd9d8fc1-0e31-4927-a1cc-33171a5e6843" data-drupal-link-system-path="node/24">Contact Us</a>
              </li>
          <li  class="menu__item">
        <a href="https://www.neonscience.org/terms-use" title="Terms &amp; Conditions" class="menu__link" data-plugin-id="menu-link-content07159864-5327-46a9-a026-e60fa4cbfa05" data-drupal-link-system-path="node/31">Terms &amp; Conditions</a>
              </li>
          <li  class="menu__item">
        <a href="https://www.neonscience.org/get-involved/work-opportunities/careers" class="menu__link" data-plugin-id="menu-link-contentcbe8afdf-f98d-429d-aa0a-842196d4654e">Careers</a>
              </li>
          <li  class="menu__item">
        <a href="https://www.neonscience.org/neon-code-conduct" class="menu__link" data-plugin-id="menu-link-content4557061a-f1f3-4fc9-a14d-d3a9517d4cfb" data-drupal-link-system-path="node/12759">Code of Conduct</a>
              </li>
        </ul>
  


  </nav>

  </div>

    </div>
    <div class="footer-bottom__copyright">
      <p>Copyright &copy; Battelle, 2026</p>
    </div>
    <div class="footer-bottom__message">
      <p>The National Ecological Observatory Network is a major facility fully funded by the U.S. National Science Foundation.</p><p>Any opinions, findings and conclusions or recommendations expressed in this material do not necessarily reflect the views of the U.S. National Science Foundation.</p>
    </div>
  </div>
</footer>

`,tx=eT.DRUPAL_FOOTER_HTML.KEY,tv=(0,eB.makeStyles)()(e=>({footerContainer:{"& .footer-bottom__wrapper":{background:"#4B372E"}}})),tk={drupalCssLoaded:!1,showSkeleton:!1},ty=e=>{let{drupalCssLoaded:t,showSkeleton:a}=(0,eJ.resolveProps)(tk,e),{classes:n}=tv(),[{isActive:r,fetches:{[tx]:s},html:{[tx]:l}}]=tt(),o="legacy";if(r)switch(s.status){case e3:o=l&&t?"drupal":"loading";break;case e4:o=t?"drupal-fallback":"loading";break;default:o=ej.default.fetchDrupalAssets?"loading":"drupal-fallback"}let d=()=>(0,i.jsx)("footer",{id:"footer",className:n.footerContainer,children:eO(tw)});switch(o){case"loading":if(!a)return d();return(0,i.jsx)("footer",{id:"footer",children:(0,i.jsx)(eo,{variant:"rectangular",height:"300px",width:"100%"})});case"drupal":return(0,i.jsx)("footer",{id:"footer",children:eO(l)});default:return d()}};ty.propTypes={drupalCssLoaded:s.default.bool,showSkeleton:s.default.bool};var t_=e.i(56333),tC={},tS=function(){let e;if(n)return tC;n=1,Object.defineProperty(tC,"__esModule",{value:!0}),tC.parseCookie=d,tC.parse=d,tC.stringifyCookie=function(e,n){let i=n?.encode||encodeURIComponent,r=[];for(let n of Object.keys(e)){let s=e[n];if(void 0===s)continue;if(!t.test(n))throw TypeError(`cookie name is invalid: ${n}`);let l=i(s);if(!a.test(l))throw TypeError(`cookie val is invalid: ${s}`);r.push(`${n}=${l}`)}return r.join("; ")},tC.stringifySetCookie=c,tC.serialize=c,tC.parseSetCookie=function(e,t){let a=t?.decode||m,n=e.length,i=u(e,0,n),r=p(e,0,i),l=-1===r?{name:"",value:a(h(e,0,i))}:{name:h(e,0,r),value:a(h(e,r+1,i))},o=i+1;for(;o<n;){let t=u(e,o,n),a=p(e,o,t),i=-1===a?h(e,o,t):h(e,o,a),r=-1===a?void 0:h(e,a+1,t);switch(i.toLowerCase()){case"httponly":l.httpOnly=!0;break;case"secure":l.secure=!0;break;case"partitioned":l.partitioned=!0;break;case"domain":l.domain=r;break;case"path":l.path=r;break;case"max-age":r&&s.test(r)&&(l.maxAge=Number(r));break;case"expires":if(!r)break;let d=new Date(r);Number.isFinite(d.valueOf())&&(l.expires=d);break;case"priority":if(!r)break;let c=r.toLowerCase();("low"===c||"medium"===c||"high"===c)&&(l.priority=c);break;case"samesite":if(!r)break;let m=r.toLowerCase();("lax"===m||"strict"===m||"none"===m)&&(l.sameSite=m)}o=t+1}return l},tC.stringifySetCookie=c,tC.serialize=c;let t=/^[\u0021-\u003A\u003C\u003E-\u007E]+$/,a=/^[\u0021-\u003A\u003C-\u007E]*$/,i=/^([.]?[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)([.][a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i,r=/^[\u0020-\u003A\u003D-\u007E]*$/,s=/^-?\d+$/,l=Object.prototype.toString,o=((e=function(){}).prototype=Object.create(null),e);function d(e,t){let a=new o,n=e.length;if(n<2)return a;let i=t?.decode||m,r=0;do{let t=p(e,r,n);if(-1===t)break;let s=u(e,r,n);if(t>s){r=e.lastIndexOf(";",t-1)+1;continue}let l=h(e,r,t);void 0===a[l]&&(a[l]=i(h(e,t+1,s))),r=s+1}while(r<n)return a}function c(e,n,s){let o="object"==typeof e?e:{...s,name:e,value:String(n)},d=("object"==typeof n?n:s)?.encode||encodeURIComponent;if(!t.test(o.name))throw TypeError(`argument name is invalid: ${o.name}`);let c=o.value?d(o.value):"";if(!a.test(c))throw TypeError(`argument val is invalid: ${o.value}`);let u=o.name+"="+c;if(void 0!==o.maxAge){if(!Number.isInteger(o.maxAge))throw TypeError(`option maxAge is invalid: ${o.maxAge}`);u+="; Max-Age="+o.maxAge}if(o.domain){if(!i.test(o.domain))throw TypeError(`option domain is invalid: ${o.domain}`);u+="; Domain="+o.domain}if(o.path){if(!r.test(o.path))throw TypeError(`option path is invalid: ${o.path}`);u+="; Path="+o.path}if(o.expires){var p;if(p=o.expires,"[object Date]"!==l.call(p)||!Number.isFinite(o.expires.valueOf()))throw TypeError(`option expires is invalid: ${o.expires}`);u+="; Expires="+o.expires.toUTCString()}if(o.httpOnly&&(u+="; HttpOnly"),o.secure&&(u+="; Secure"),o.partitioned&&(u+="; Partitioned"),o.priority)switch("string"==typeof o.priority?o.priority.toLowerCase():void 0){case"low":u+="; Priority=Low";break;case"medium":u+="; Priority=Medium";break;case"high":u+="; Priority=High";break;default:throw TypeError(`option priority is invalid: ${o.priority}`)}if(o.sameSite)switch("string"==typeof o.sameSite?o.sameSite.toLowerCase():o.sameSite){case!0:case"strict":u+="; SameSite=Strict";break;case"lax":u+="; SameSite=Lax";break;case"none":u+="; SameSite=None";break;default:throw TypeError(`option sameSite is invalid: ${o.sameSite}`)}return u}function u(e,t,a){let n=e.indexOf(";",t);return -1===n?a:n}function p(e,t,a){let n=e.indexOf("=",t);return n<a?n:-1}function h(e,t,a){let n=t,i=a;do{let t=e.charCodeAt(n);if(32!==t&&9!==t)break}while(++n<i)for(;i>n;){let t=e.charCodeAt(i-1);if(32!==t&&9!==t)break;i--}return e.slice(n,i)}function m(e){if(-1===e.indexOf("%"))return e;try{return decodeURIComponent(e)}catch(t){return e}}return tC}();function tj(e,t={}){var a;let n=(a=e)&&"j"===a[0]&&":"===a[1]?a.substr(2):a;if(!t.doNotParse)try{return JSON.parse(n)}catch(e){}return e}class tT{constructor(e,t={}){this.changeListeners=[],this.HAS_DOCUMENT_COOKIE=!1,this.update=()=>{if(!this.HAS_DOCUMENT_COOKIE)return;let e=this.cookies;this.cookies=tS.parse(document.cookie),this._checkChanges(e)};const a="u"<typeof document?"":document.cookie;this.cookies=function(e){return"string"==typeof e?tS.parse(e):"object"==typeof e&&null!==e?e:{}}(e||a),this.defaultSetOptions=t,this.HAS_DOCUMENT_COOKIE=function(){let e="u"<typeof globalThis?void 0:globalThis.TEST_HAS_DOCUMENT_COOKIE;return"boolean"==typeof e?e:"object"==typeof document&&"string"==typeof document.cookie}()}_emitChange(e){for(let t=0;t<this.changeListeners.length;++t)this.changeListeners[t](e)}_checkChanges(e){new Set(Object.keys(e).concat(Object.keys(this.cookies))).forEach(t=>{e[t]!==this.cookies[t]&&this._emitChange({name:t,value:tj(this.cookies[t])})})}_startPolling(){this.pollingInterval=setInterval(this.update,300)}_stopPolling(){this.pollingInterval&&clearInterval(this.pollingInterval)}get(e,t={}){return t.doNotUpdate||this.update(),tj(this.cookies[e],t)}getAll(e={}){e.doNotUpdate||this.update();let t={};for(let a in this.cookies)t[a]=tj(this.cookies[a],e);return t}set(e,t,a){a=a?Object.assign(Object.assign({},this.defaultSetOptions),a):this.defaultSetOptions;let n="string"==typeof t?t:JSON.stringify(t);this.cookies=Object.assign(Object.assign({},this.cookies),{[e]:n}),this.HAS_DOCUMENT_COOKIE&&(document.cookie=tS.serialize(e,n,a)),this._emitChange({name:e,value:t,options:a})}remove(e,t){let a=t=Object.assign(Object.assign(Object.assign({},this.defaultSetOptions),t),{expires:new Date(1970,1,1,0,0,1),maxAge:0});this.cookies=Object.assign({},this.cookies),delete this.cookies[e],this.HAS_DOCUMENT_COOKIE&&(document.cookie=tS.serialize(e,"",a)),this._emitChange({name:e,value:void 0,options:t})}addChangeListener(e){this.changeListeners.push(e),this.HAS_DOCUMENT_COOKIE&&1===this.changeListeners.length&&("object"==typeof window&&"cookieStore"in window?window.cookieStore.addEventListener("change",this.update):this._startPolling())}removeChangeListener(e){let t=this.changeListeners.indexOf(e);t>=0&&this.changeListeners.splice(t,1),this.HAS_DOCUMENT_COOKIE&&0===this.changeListeners.length&&("object"==typeof window&&"cookieStore"in window?window.cookieStore.removeEventListener("change",this.update):this._stopPolling())}removeAllChangeListeners(){for(;this.changeListeners.length>0;)this.removeChangeListener(this.changeListeners[0])}}var tE=e.i(78467);let tN=(0,eB.makeStyles)()(e=>({browserWarning:{backgroundColor:e.palette.error.main},browserWarningMessage:{display:"flex",alignItems:"center"},buttonRow:{marginTop:e.spacing(1),display:"flex",justifyContent:"space-around"}})),tA=new tT,tO=()=>{let{classes:e}=tN(),t=-1!==navigator.userAgent.indexOf("MSIE")||navigator.appVersion.indexOf("Trident/")>-1,[a,n]=(0,r.useState)(t);if(!t||tA.get("ignoreIE11Warning"))return null;let s={color:"inherit",target:"_blank"},l=(0,i.jsx)("span",{id:"browser-warning",className:e.browserWarningMessage,"data-selenium":"neon-page.browser-warning",children:(0,i.jsxs)("div",{children:[(0,i.jsx)("b",{children:"Your browser has limited support on the NEON Data Portal."}),(0,i.jsx)("br",{}),"Some parts of the portal will not work for you.",(0,i.jsx)("br",{}),"Please use a modern browser for full support.",(0,i.jsx)("br",{}),(0,i.jsxs)("div",{className:e.buttonRow,children:[(0,i.jsx)(R.default,{...s,href:"https://www.mozilla.org/en-US/firefox/new/",children:"Firefox"}),(0,i.jsx)(R.default,{...s,href:"https://www.google.com/chrome/",children:"Chrome"}),(0,i.jsx)(R.default,{...s,href:"https://www.apple.com/safari/",children:"Safari"}),(0,i.jsx)(R.default,{...s,href:"https://www.microsoft.com/en-us/windows/microsoft-edge",children:"Edge"})]})]})});return(0,i.jsx)(ev,{anchorOrigin:{vertical:"bottom",horizontal:"right"},ContentProps:{"aria-describedby":"browser-warning"},open:a,children:(0,i.jsx)(eg.default,{className:e.browserWarning,message:l,action:(0,i.jsx)(z.default,{"aria-label":"close",color:"inherit",onClick:()=>{tA.set("ignoreIE11Warning",!0,{path:"/",maxAge:86400}),n(!1)},size:"large",children:(0,i.jsx)(tE.default,{})},"close")})})};var tL=e.i(8659),tM=e.i(65678);let tR=(0,eB.makeStyles)()(e=>({notification:{color:e.palette.text.primary,backgroundColor:eE.COLORS.GOLD[50],border:`1px solid ${eE.COLORS.GOLD[300]}`,borderRadius:"4px",marginLeft:e.spacing(3),[e.breakpoints.up("sm")]:{maxWidth:"75%"},[e.breakpoints.up("md")]:{maxWidth:"50%"},[e.breakpoints.up("lg")]:{maxWidth:"33%"}},notificationDiv:{paddingTop:e.spacing(2),paddingBottom:e.spacing(2)}})),tD={notifications:[],onHideNotifications:null},tI=e=>{let t=(0,eJ.resolveProps)(tD,e),{classes:a}=tR(),{notifications:n,onHideNotifications:r}=t;return!n.length||n.every(e=>e.dismissed)?null:(0,i.jsx)(ev,{open:!0,anchorOrigin:{vertical:"bottom",horizontal:"right"},slotProps:{content:{"aria-describedby":"neon-data-portal-notifications"}},children:(0,i.jsx)(eg.default,{className:a.notification,message:(0,i.jsx)("div",{id:"neon-data-portal-notifications",children:n.map(e=>!0===e.isReactNode?(0,i.jsxs)("div",{children:[(0,i.jsx)("div",{className:a.notificationDiv,children:e.message}),(0,i.jsx)(P.default,{})]},e.id):(0,i.jsxs)("div",{children:[(0,i.jsx)("div",{className:a.notificationDiv,dangerouslySetInnerHTML:{__html:e.message}}),(0,i.jsx)(P.default,{})]},e.id))}),action:"function"==typeof r?(0,i.jsx)(z.default,{color:"default",size:"small","aria-label":"close",onClick:r,children:(0,i.jsx)(tE.default,{})},"close"):null})})};tI.propTypes={notifications:s.default.arrayOf(s.default.shape({id:s.default.string.isRequired,message:s.default.oneOfType([s.default.node,s.default.string]).isRequired,dismissed:s.default.bool.isRequired})),onHideNotifications:s.default.func};let tP=e=>{let t=0;if(!e.length)return t;for(let a=0;a<e.length;a+=1)t=(t<<5)-t+e.charCodeAt(a),t&=t;return`notification-${t}`};var tz=e.i(93094);let tF=(0,i.jsx)(F.default,{href:ej.default.route.buildAccountRoute(),target:"_blank",children:"My Account"}),tH=(0,i.jsx)(F.default,{href:tM.default.getContactUsPath(),target:"_blank",children:"Contact Us"}),tB=(0,i.jsxs)("div",{children:[(0,i.jsx)(g.default,{variant:"subtitle2",gutterBottom:!0,children:"API Token Expiration Notice"}),(0,i.jsxs)(g.default,{variant:"body2",children:["An API Token associated with your account is expiring soon. If you would like a new token, please navigate to your ",tF," page. At the bottom of your account page, click on the “Get API Token” button. You can delete an expired token by clicking the three dots under the Actions column. If you have any questions or issues, please let us know through our ",tH," form."]})]}),t$=(e,t)=>{let a={...e};switch(t.type){case"fetchNotifications":return a.fetchStatus=eH.FETCH_STATUS.FETCHING,a;case"fetchNotificationsSuccess":return a.fetchStatus=eH.FETCH_STATUS.SUCCESS,a;case"fetchNotificationsError":return a.fetchStatus=eH.FETCH_STATUS.ERROR,a;case"userInfoNotificationsFetchCompleted":return a.userInfoNotificationsFetchCompleted=!0,a;case"setUserInfoNotifications":return a.userInfoNotifications=t.userInfoNotifications,a;default:return e}},tU=new tT,tW=new tL.Subject,tV={initialNotification:null},tG=e=>{let{initialNotification:t}=(0,eJ.resolveProps)(tV,e),a=tU.get("dismissed-notifications"),n=(0,r.useMemo)(()=>a||[],[a]),s={fetchStatus:null,userInfoNotificationsFetchCompleted:!1,userInfoNotifications:[]},l=[];if(null!==t&&t.length){let e=tP(t);s.fetchStatus="success",l=[{id:e,message:t,dismissed:n.includes(e)}]}let[{isActive:o,auth:{userData:d}}]=eH.default.useNeonAuthContextState(),[c,u]=(0,r.useReducer)(t$,s),[p,h]=(0,r.useState)(l),[m,f]=(0,r.useState)([]),{userInfoNotificationsFetchCompleted:g,userInfoNotifications:b}=c,w=(0,r.useCallback)(e=>{if(u({type:"fetchNotificationsSuccess"}),!Array.isArray(e.notifications))return;let t=[...m];e.notifications.forEach(e=>{let a=tP(e),i=n.includes(a);t.push({id:a,message:e,dismissed:i})}),f(t)},[u,f,m,n]),x=(0,r.useCallback)(()=>{u({type:"fetchNotificationsError"}),f([])},[u,f]);(0,r.useEffect)(()=>{if(null===c.fetchStatus){if(ej.default.auth0DisableApi)return void u({type:"fetchNotificationsSuccess"});u({type:"fetchNotifications"}),(0,tz.getJson)(ej.default.getFullAuthPath("notifications"),w,x,tW)}},[c,w,x,n]),(0,r.useEffect)(()=>{if(d&&!g&&o&&d?.data?.user&&(u({type:"userInfoNotificationsFetchCompleted"}),d?.data?.expiringApiToken===!0)){let e=tP(JSON.stringify({message:tB})),t=n.includes(e),a=[...b];a.push({id:e,dismissed:t,message:tB,isReactNode:!0}),u({type:"setUserInfoNotifications",userInfoNotifications:a})}},[d,g,o,u,n,b]);let v=[...p,...m,...b];return(0,i.jsx)(tI,{notifications:v,onHideNotifications:()=>{let e=[...p,...m,...b].map(e=>e.id);tU.set("dismissed-notifications",e,{path:"/",maxAge:86400}),(0,e$.existsNonEmpty)(p)&&h(p.map(e=>({...e,dismissed:!0}))),(0,e$.existsNonEmpty)(m)&&f(m.map(e=>({...e,dismissed:!0}))),(0,e$.existsNonEmpty)(b)&&u({type:"setUserInfoNotifications",userInfoNotifications:b.map(e=>({...e,dismissed:!0}))})}})};var tq=e.i(27099);let tY=eT.DRUPAL_THEME_CSS.KEY;window.gtmDataLayer||(window.gtmDataLayer=[]);let tX=(0,eB.makeStyles)()((e,{sidebarWidth:t,sidebarDisableMaxHeight:a})=>({outerPageContainer:{display:"flex",position:"relative",minHeight:e.spacing(30),borderTop:"2px solid transparent",paddingLeft:"0px",paddingRight:"0px",[e.breakpoints.down("md")]:{paddingBottom:e.spacing(2.5),flexDirection:"column"}},pageContent:{display:"block",verticalAlign:"top",position:"relative",padding:e.spacing(4,8,12,8),width:`calc(100% - ${t}px)`,[e.breakpoints.down("md")]:{width:"100%",display:"block",padding:e.spacing(3,5,8,5)},'& a:not([class]), a[class=""]':{color:eE.COLORS.LIGHT_BLUE[500],textDecoration:"none"},'& a:hover:not([class]), a:hover[class=""]':{textDecoration:"underline"}},breadcrumbs:{margin:e.spacing(2,0,4,0),[e.breakpoints.down("md")]:{margin:e.spacing(1,0,2,0)}},sidebarContainer:{display:"block",verticalAlign:"top",backgroundColor:eE.COLORS.GREY[50],padding:e.spacing(5,4),lineHeight:1.43,[e.breakpoints.down("md")]:{display:"inline-block",width:"100%",maxHeight:a?"unset":"calc(100vh - 84px)",padding:e.spacing(2.5,2),position:"sticky",top:"-2px",boxShadow:"0px 1px 3px rgba(0, 0, 0, 0.25), 0px 1px 1px rgba(0, 0, 0, 0.25)",zIndex:2},[e.breakpoints.down("sm")]:{padding:e.spacing(1.5)}},sidebarInnerStickyContainer:{top:"40px"},sidebarTitleContainer:{display:"flex",alignItems:"center",justifyContent:"space-between"},sidebarTitle:{fontWeight:700,[e.breakpoints.down("md")]:{overflow:"hidden",whiteSpace:"nowrap",textOverflow:"ellipsis"},[e.breakpoints.only("sm")]:{marginRight:e.spacing(1.5)}},sidebarSubtitle:{color:eE.COLORS.GREY[300],marginTop:e.spacing(1),[e.breakpoints.down("md")]:{overflow:"hidden",whiteSpace:"nowrap",textOverflow:"ellipsis",marginTop:"0px"},[e.breakpoints.down("sm")]:{display:"none"}},sidebarTitlesContainer:{minWidth:"0px",paddingRight:e.spacing(1),[e.breakpoints.only("sm")]:{display:"flex",alignItems:"baseline"}},sidebarLink:{cursor:"pointer",display:"block",fontSize:"0.875rem",marginBottom:"12px"},sidebarLinkCurrent:{fontWeight:700,color:eE.COLORS.GREY[500],textDecoration:"none","&:hover":{transition:"all 0.45s",color:eE.COLORS.GREY[900]}},sidebarLinkIcon:{marginBottom:"-5px",marginRight:"5px",fontSize:"1.3rem"},sidebarLinksContainer:{flex:"1 1 auto",overflowY:"auto",minHeight:"0px"},sidebarDivider:{margin:"24px 0px",[e.breakpoints.down("md")]:{margin:"16px 0px"},[e.breakpoints.down("sm")]:{margin:"8px 0px 12px 0px"}},backdropPaper:{display:"flex",flexDirection:"column",alignItems:"center",justifyContent:"center",textAlign:"center",borderRadius:"4px",padding:e.spacing(3),position:"sticky",top:e.spacing(12),left:0,right:0,marginLeft:"auto",marginRight:"auto",width:"70%",[e.breakpoints.up("lg")]:{width:"50%"}},pageTitle:{margin:e.spacing(3,0,4,0),[e.breakpoints.up("sm")]:{margin:e.spacing(3,0,4,0)}},pageSubtitle:{maxWidth:"660px",color:eE.COLORS.GREY[500],lineHeight:"1.5",fontSize:"1.125rem",marginTop:e.spacing(-1),marginBottom:e.spacing(4)},dismissOverlay:{width:"100%",textAlign:"right",marginTop:e.spacing(2)}})),tK=(e,t)=>{let a={...e};switch(t.type){case"fetchDrupalCss":return a.fetchStatus=t_.FETCH_STATUS.FETCHING,a;case"fetchDrupalCssSuccess":return a.fetchStatus=t_.FETCH_STATUS.SUCCESS,a;case"fetchDrupalCssError":return a.fetchStatus=t_.FETCH_STATUS.ERROR,a;default:return e}},tZ={breadcrumbHomeHref:"/",breadcrumbs:[],customHeader:null,customFooter:null,customizeAuthContainer:!1,authContextDisable:!0,showHeaderSkeleton:!1,showFooterSkeleton:!1,error:null,loading:null,notification:null,notificationsDisable:!0,outerPageContainerMaxWidth:"2000px",progress:null,resetStateAfterRuntimeError:()=>{},sidebarContent:null,sidebarContentResponsive:!1,sidebarContainerClassName:null,sidebarLinks:null,sidebarLinksAdditionalContent:null,sidebarLinksAsStandaloneChildren:!1,sidebarSubtitle:null,sidebarTitle:null,sidebarWidth:300,sidebarUnsticky:!1,sidebarDisableMaxHeight:!1,subtitle:null,title:null,unstickyDrupalHeader:!0,NeonContextProviderProps:{},NeonAuthContextProviderProps:{},NeonPageAssetsContextProviderProps:{}},tJ=e=>{var t;let{breadcrumbHomeHref:a,breadcrumbs:n,customHeader:s,customFooter:u,customizeAuthContainer:p,authContextDisable:h,showHeaderSkeleton:m,showFooterSkeleton:f,error:b,loading:w,notification:x,notificationsDisable:v,outerPageContainerMaxWidth:k,progress:y,resetStateAfterRuntimeError:_,sidebarContent:C,sidebarContentResponsive:S,sidebarContainerClassName:j,sidebarLinks:T,sidebarLinksAdditionalContent:E,sidebarLinksAsStandaloneChildren:N,sidebarSubtitle:A,sidebarTitle:O,sidebarWidth:L,sidebarUnsticky:B,sidebarDisableMaxHeight:$,subtitle:U,title:W,unstickyDrupalHeader:V,NeonContextProviderProps:G,NeonAuthContextProviderProps:q,NeonPageAssetsContextProviderProps:Y,children:X}=(0,eJ.resolveProps)(tZ,e),K=null!==C,Z=!C&&Array.isArray(T)&&T.length>0,Q=K||Z,{classes:ee,theme:et}=tX({sidebarWidth:Q?L:0,sidebarDisableMaxHeight:$}),[{isActive:ea}]=t_.default.useNeonContextState(),[{isActive:en}]=eH.default.useNeonAuthContextState(),[{isActive:ei}]=tt(),er=(0,r.useRef)(null),es=(0,r.useRef)(null),el=(0,r.useRef)(null),ed=(0,r.useRef)(null),ec=(0,d.default)(et.breakpoints.down("md")),[eu,ep]=(0,r.useState)(!1),eh=(0,tq.useNetworkAvailability)(),em=ej.default.fetchDrupalAssets&&!(s&&u),ef=!!Z&&!!N&&T.every(e=>e.component),eg=(0,r.useMemo)(()=>Z?Object.fromEntries(T.map((e,t)=>[e.hash||"#",t])):{},[Z,T]),eb=(()=>{if(!Z||!T.length)return"#";if(5===T.length)return T[3].hash;if(4===T.length){let{pathname:e}=window.location,t=T.find(t=>!!t.match&&(Array.isArray(t.match)?t.match:[t.match]).some(t=>e.includes(t)));return t?.hash||"#"}return"#"})(),ew=(0,r.useRef)(!1),[ex,eA]=(0,r.useState)(!1),eO=(0,eE.getThemeSpacingNumber)(et.spacing(5)),eL=(0,r.useCallback)((e,t)=>{if(!Z||t||!es.current)return -1;let a=(er.current||{}).offsetHeight||0,n=ec&&(el.current||{}).offsetHeight||0;if("#"===e)return 0;let i="string"==typeof e&&e.startsWith("#")?es.current?.querySelector(`#${CSS.escape(e.slice(1))}`):null;return i?i.offsetTop+a-n-eO:-1},[Z,ec,es,er,el,eO]),eM=(0,r.useCallback)(e=>{window.location.hash=e},[]);(0,r.useLayoutEffect)(()=>{if(b||w||!Z)return()=>{};let e=()=>{let{hash:e}=document.location;eb!==e&&(ef||window.setTimeout(()=>{window.scrollTo(0,eL(e,ef)-40)},0))};if(document.location.hash&&!ew.current&&(ew.current=!0,e()),!B&&Z&&ed.current&&!$){let e=window.innerHeight-ed.current.offsetTop-104;ed.current.style.maxHeight=`${e}px`}if(ef)return window.addEventListener("hashchange",e),()=>{window.removeEventListener("hashchange",e)};let t=()=>{let e,t,a=T.map(e=>({y:eL(e.hash||"#",ef),hash:e.hash||"#"}));(e=window.innerHeight||(document.documentElement||document.body).clientHeight,t=Math.max(document.body.scrollHeight,document.documentElement.scrollHeight,document.body.offsetHeight,document.documentElement.offsetHeight,document.body.clientHeight,document.documentElement.clientHeight),(window.pageYOffset||(document.documentElement||document.body.parentNode||document.body).scrollTop)/(t-e)>=.99)?a[a.length-1].hash:a.reduce((e,t)=>-1!==t.y&&window.scrollY>=t.y-80?t.hash:e,T[0].hash||"#")};return window.addEventListener("scroll",t),()=>{window.removeEventListener("scroll",t)}},[b,w,T,eg,B,$,Z,ew,eb,eM,ed,eL,ef]);let eR={fetchStatus:em?t_.FETCH_STATUS.AWAITING_CALL:t_.FETCH_STATUS.SUCCESS},[eD,eI]=(0,r.useReducer)(tK,eR),eP=eD.fetchStatus===t_.FETCH_STATUS.SUCCESS;(0,r.useEffect)(()=>{em&&eD.fetchStatus===t_.FETCH_STATUS.AWAITING_CALL&&(eI({type:"fetchDrupalCss"}),fetch(eT[tY].url).then(e=>{if(!e.ok)throw Error("Failed to fetch Drupal theme CSS");return e.text()}).then(e=>{let t=document.createElement("style");t.setAttribute("data-meta","drupal-theme"),t.setAttribute("data-meta-runtime","drupal-theme"),t.textContent=((e,t=!1)=>{let a=e;if(!(0,e$.isStringNonEmpty)(a))return a;let n=a.match(/^(.*url\(["']((?!data|https)).*)$/mg);return(0,e$.exists)(n)&&n.forEach(e=>{let n=!0;if(t){let t=/(?<relative>url\(["'](?<relativePathSegments>\.\.\/\.\.\/\.\.\/\.\.\/|\.\.\/\.\.\/\.\.\/|\.\.\/\.\.\/|\.\.\/)(?<path>images\/.+)["']\))/.exec(e);if((0,e$.exists)(t)&&t.length>0&&(0,e$.exists)(t.groups)&&(0,e$.isStringNonEmpty)(t.groups?.relative)&&(0,e$.isStringNonEmpty)(t.groups?.path)){let e=t.groups?.relative,i=t.groups?.path,r=`${ej.default.getWebHost()}/themes/custom/neon/${i}`,s=`url("${r}")`;a=a.replace(e,s),n=!1}}if(n){let t=e.endsWith("}")?`/* ${e.slice(0,-1)} */ }`:`/* ${e} */`;a=a.replace(e,t)}}),a})(e,!0),document.head.appendChild(t);try{let e=document.head.querySelector('link[data-meta="drupal-theme"]');null!=e&&e.remove()}catch(e){console.error(e)}eI({type:"fetchDrupalCssSuccess"})}).catch(e=>{console.error(e),eI({type:"fetchDrupalCssSuccess"})}))},[em,eD,eI]);let ez=e=>(0,i.jsx)(c.default,{open:!eu,children:(0,i.jsxs)(H.default,{className:ee.backdropPaper,children:[e,(0,i.jsx)("div",{className:ee.dismissOverlay,children:(0,i.jsx)(R.default,{size:"small",startIcon:(0,i.jsx)(ek.default,{}),variant:"outlined",onClick:()=>{ep(!0)},children:"Dismiss"})})]})}),eF=()=>{let e={};k&&(e.maxWidth=!Q||ec?k:`calc(${k} - ${L+48}px)`);let t=X;if(Z&&ef){let e=T[eg[eb]||0].component;t=(0,i.jsx)(e,{})}return(0,i.jsxs)(i.Fragment,{children:[s?(0,i.jsx)("header",{ref:er,children:s}):(0,i.jsx)(tb,{customizeAuthContainer:p,ref:er,unstickyDrupalHeader:V,drupalCssLoaded:eP,showSkeleton:m}),(0,i.jsxs)(I.default,{className:ee.outerPageContainer,style:e,children:[(()=>{if(!Q)return null;let e=ec?{}:{width:`${L}px`},t=ec?{}:{width:`${L-(0,eE.getThemeSpacingNumber)(et.spacing(8))}px`},a=j?`${ee.sidebarContainer} ${j}`:ee.sidebarContainer;if(K&&!S)return(0,i.jsx)("div",{ref:el,className:a,style:e,children:C});let n=()=>O||W?(0,i.jsx)("div",{className:ee.sidebarTitlesContainer,children:w||b?(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(eo,{width:200,height:22,style:{marginBottom:Theme.spacing(1)}}),A?(0,i.jsx)(eo,{width:120,height:16,style:{marginBottom:Theme.spacing(1)}}):null]}):(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(g.default,{variant:"h5",component:"h3",className:ee.sidebarTitle,children:O||W}),A?(0,i.jsx)(g.default,{variant:"subtitle2",component:"h4",className:ee.sidebarSubtitle,children:A}):null]})}):null;if(K)return(0,i.jsx)("div",{ref:el,className:a,style:e,children:(0,i.jsxs)("div",{className:B||ec?null:`${ee.sidebarInnerStickyContainer} neon__sidebar-sticky`,children:[(0,i.jsxs)("div",{className:ee.sidebarTitleContainer,children:[n(),ec?(0,i.jsx)(z.default,{size:"small",onClick:()=>eA(!ex),children:ex?(0,i.jsx)(ey.default,{fontSize:"large"}):(0,i.jsx)(eC.default,{fontSize:"large"})}):null]}),!ec||ex?(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(P.default,{className:ee.sidebarDivider,style:{...t}}),C]}):null]})});let r=(e,t=!1)=>{if(!e)return null;let{name:a,hash:n="#",icon:r}=e;if(w||b)return(0,i.jsx)(eo,{width:`${Math.floor(50+50*Math.random())}%`,height:16,style:{marginBottom:"16px"}},a);let s=r?(0,i.jsx)(r,{className:ee.sidebarLinkIcon}):null;return(0,i.jsxs)(F.default,{href:n,onClick:ef?()=>{ex&&eA(!1)}:null,className:eb===n?`${ee.sidebarLink} ${ee.sidebarLinkCurrent}`:ee.sidebarLink,style:t?{marginBottom:"0px"}:{},children:[s,a]},a)},s=(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)("div",{ref:ed,className:ee.sidebarLinksContainer,children:T.map(e=>r(e))}),ec?null:(0,i.jsx)(P.default,{className:ee.sidebarDivider,style:{marginBottom:"0px",...t}})]}),l=(0,i.jsx)("div",{className:ee.sidebarLinksContainer,children:r(T[eg[eb]],!0)});return(0,i.jsx)("div",{ref:el,className:a,style:e,children:(0,i.jsxs)("div",{className:B||ec?null:`${ee.sidebarInnerStickyContainer} neon__sidebar-sticky`,children:[(0,i.jsxs)("div",{className:ee.sidebarTitleContainer,children:[n(),ec?(0,i.jsx)(z.default,{size:"small",onClick:()=>eA(!ex),children:ex?(0,i.jsx)(ey.default,{fontSize:"large"}):(0,i.jsx)(eC.default,{fontSize:"large"})}):null]}),(0,i.jsx)(P.default,{className:ee.sidebarDivider,style:{...t}}),E&&(!ec||ex)?(0,i.jsxs)(i.Fragment,{children:[E,(0,i.jsx)(P.default,{className:ee.sidebarDivider,style:{...t}})]}):null,ec&&!ex?l:s]})})})(),(0,i.jsxs)("div",{className:ee.pageContent,style:{top:Q&&!n.length?"12px":"0px"},"data-selenium":"neon-page.content",ref:es,children:[n.length?(0,i.jsxs)(M,{"aria-label":"Breadcrumbs","data-selenium":"neon-page.breadcrumbs",className:ee.breadcrumbs,children:[(0,i.jsx)(F.default,{href:a,children:(0,i.jsx)(eS.default,{title:"Home",fontSize:"small",style:{marginBottom:"-4px"}})},(0,l.default)()),n.map((e,t)=>t!==n.length-1?(0,i.jsx)(F.default,{href:e.href,children:e.name},(0,l.default)()):(0,i.jsx)(g.default,{color:"textPrimary",children:e.name},"{idx}"))]}):null,(()=>{if((w||b)&&!W)return(0,i.jsx)(eo,{width:"45%",height:24,style:{margin:et.spacing(2,0,4,0)}});if((!W||!W.length)&&!ef)return null;let e=W||"";if(ef){let t=T[eg[eb]||0];e=t.pageTitle||t.name}return(0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(g.default,{"data-selenium":"neon-page.title",variant:"h3",component:"h1",className:ee.pageTitle,children:e}),U?(0,i.jsx)(g.default,{"data-selenium":"neon-page.subtitle",variant:"subtitle1",component:"p",className:ee.pageSubtitle,children:U}):null]})})(),t]})]}),!0===v?null:(0,i.jsx)(tG,{initialNotification:x}),(()=>{if(eh)return null;let e=(0,i.jsxs)("div",{style:{display:"flex"},children:[(0,i.jsx)(e_.default,{color:"warning",style:{marginRight:et.spacing(1.5)}}),(0,i.jsx)(g.default,{variant:"body2",style:{marginTop:"2px"},children:"No internet connection. Check your connection."})]});return(0,i.jsx)(ev,{open:!0,anchorOrigin:{vertical:"top",horizontal:"center"},message:e,slots:{transition:e=>(0,i.jsx)(J,{...e,direction:"down"})}})})(),(0,i.jsx)(tO,{}),u?(0,i.jsx)("footer",{children:u}):(0,i.jsx)(ty,{drupalCssLoaded:eP,showSkeleton:f}),!w||b?null:ez((0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(g.default,{variant:"h5",component:"h3",gutterBottom:!0,children:w}),null===y?(0,i.jsx)(D.default,{}):(0,i.jsx)(D.default,{variant:"determinate",value:y})]})),b?ez((0,i.jsxs)(i.Fragment,{children:[(0,i.jsx)(e_.default,{fontSize:"large",color:"error"}),(0,i.jsx)(g.default,{variant:"h5",component:"h3",style:{marginTop:et.spacing(1)},children:b})]})):null]})},eB=ea?eF():(0,i.jsx)(t_.default.Provider,{...G,children:eF()});return(0,i.jsx)(o.ErrorBoundary,{FallbackComponent:eN.default,onReset:_,children:(t=!0===h||en?eB:(0,i.jsx)(eH.default.Provider,{...q,children:eB}),ei?t:(0,i.jsx)(e9,{...Y,fetchPartials:em,children:t}))})},tQ=s.default.oneOfType([s.default.arrayOf(s.default.oneOfType([s.default.node,s.default.string])),s.default.node,s.default.string]);tJ.propTypes={breadcrumbHomeHref:s.default.string,breadcrumbs:s.default.arrayOf(s.default.shape({name:s.default.string.isRequired,href:s.default.string})),customHeader:s.default.node,customFooter:s.default.node,customizeAuthContainer:s.default.bool,authContextDisable:s.default.bool,showHeaderSkeleton:s.default.bool,showFooterSkeleton:s.default.bool,error:s.default.string,loading:s.default.string,notification:s.default.string,notificationsDisable:s.default.bool,outerPageContainerMaxWidth:s.default.string,progress:s.default.number,resetStateAfterRuntimeError:s.default.func,sidebarContent:tQ,sidebarContentResponsive:s.default.bool,sidebarContainerClassName:s.default.string,sidebarLinks:s.default.arrayOf(s.default.shape({name:s.default.string.isRequired,pageTitle:s.default.string,hash:s.default.string,icon:s.default.oneOfType([s.default.func,s.default.object]),component:s.default.oneOfType([s.default.func,s.default.object])})),sidebarLinksAdditionalContent:tQ,sidebarLinksAsStandaloneChildren:s.default.bool,sidebarSubtitle:s.default.string,sidebarTitle:s.default.string,sidebarWidth:s.default.number,sidebarUnsticky:s.default.bool,sidebarDisableMaxHeight:s.default.bool,subtitle:s.default.oneOfType([s.default.string,tQ]),title:s.default.oneOfType([s.default.string,tQ]),unstickyDrupalHeader:s.default.bool,NeonContextProviderProps:s.default.shape(t_.default.ProviderPropTypes),NeonAuthContextProviderProps:s.default.shape(eH.default.ProviderPropTypes),NeonPageAssetsContextProviderProps:s.default.shape(te),children:tQ.isRequired};var t0=e.i(37560);function t1(e){return(0,T.default)("MuiAccordionActions",e)}(0,j.default)("MuiAccordionActions",["root","spacing"]);let t2=(0,m.styled)("div",{name:"MuiAccordionActions",slot:"Root",overridesResolver:(e,t)=>{let{ownerState:a}=e;return[t.root,!a.disableSpacing&&t.spacing]}})({display:"flex",alignItems:"center",padding:8,justifyContent:"flex-end",variants:[{props:e=>!e.disableSpacing,style:{"& > :not(style) ~ :not(style)":{marginLeft:8}}}]}),t4=r.forwardRef(function(e,t){let a=(0,f.useDefaultProps)({props:e,name:"MuiAccordionActions"}),{className:n,disableSpacing:i=!1,...r}=a,s={...a,disableSpacing:i},l=(e=>{let{classes:t,disableSpacing:a}=e;return(0,p.default)({root:["root",!a&&"spacing"]},t1,t)})(s);return(0,v.jsx)(t2,{className:(0,u.default)(l.root,n),ref:t,ownerState:s,...r})});var t3=e.i(42691),t7=e.i(47403),t5=e.i(26044),t8=e.i(73814),t6=e.i(29827),t9=e.i(48618),ae=e.i(42830),at=e.i(25778);function aa(e){return(0,T.default)("MuiSwitch",e)}let an=(0,j.default)("MuiSwitch",["root","edgeStart","edgeEnd","switchBase","colorPrimary","colorSecondary","sizeSmall","sizeMedium","checked","disabled","input","thumb","track"]);var ai=e.i(792),ar=e.i(26365),as=e.i(56958);let al=(0,m.styled)("span",{name:"MuiSwitch",slot:"Root",overridesResolver:(e,t)=>{let{ownerState:a}=e;return[t.root,a.edge&&t[`edge${(0,em.default)(a.edge)}`],t[`size${(0,em.default)(a.size)}`]]}})({display:"inline-flex",width:58,height:38,overflow:"hidden",padding:12,boxSizing:"border-box",position:"relative",flexShrink:0,zIndex:0,verticalAlign:"middle","@media print":{colorAdjust:"exact"},variants:[{props:{edge:"start"},style:{marginLeft:-8}},{props:{edge:"end"},style:{marginRight:-8}},{props:{size:"small"},style:{width:40,height:24,padding:7,[`& .${an.thumb}`]:{width:16,height:16},[`& .${an.switchBase}`]:{padding:4,[`&.${an.checked}`]:{transform:"translateX(16px)"}}}}]}),ao=(0,m.styled)(at.default,{name:"MuiSwitch",slot:"SwitchBase",overridesResolver:(e,t)=>{let{ownerState:a}=e;return[t.switchBase,{[`& .${an.input}`]:t.input},"default"!==a.color&&t[`color${(0,em.default)(a.color)}`]]}})((0,w.default)(({theme:e})=>({position:"absolute",top:0,left:0,zIndex:1,color:e.vars?e.vars.palette.Switch.defaultColor:`${"light"===e.palette.mode?e.palette.common.white:e.palette.grey[300]}`,...(0,q.getTransitionStyles)(e,["left","transform"],{duration:e.transitions.duration.shortest}),[`&.${an.checked}`]:{transform:"translateX(20px)"},[`&.${an.disabled}`]:{color:e.vars?e.vars.palette.Switch.defaultDisabledColor:`${"light"===e.palette.mode?e.palette.grey[100]:e.palette.grey[600]}`},...e.focusVisible?{[`&.${ai.default.focusVisible} ~ .${an.track}`]:{...ar.outsetFocusRing,...e.focusVisible},[`&.${an.checked} + .${an.track}`]:{backgroundColor:e.alpha(e.vars?e.vars.palette.common.onBackground:`${"light"===e.palette.mode?e.palette.common.black:e.palette.common.white}`,.5)},[`&.${an.disabled} + .${an.track}`]:{backgroundColor:e.alpha(e.vars?e.vars.palette.common.onBackground:`${"light"===e.palette.mode?e.palette.common.black:e.palette.common.white}`,e.vars?e.vars.opacity.switchTrackDisabled:`${"light"===e.palette.mode?.12:.2}`)}}:{[`&.${an.checked} + .${an.track}`]:{opacity:.5},[`&.${an.disabled} + .${an.track}`]:{opacity:e.vars?e.vars.opacity.switchTrackDisabled:`${"light"===e.palette.mode?.12:.2}`}},[`& .${an.input}`]:{left:"-100%",width:"300%"}})),(0,w.default)(({theme:e})=>({"&:hover":{backgroundColor:e.alpha((e.vars||e).palette.action.active,(e.vars||e).palette.action.hoverOpacity),"@media (hover: none)":{backgroundColor:"transparent"}},variants:[...Object.entries(e.palette).filter((0,ae.default)(["light"])).map(([t])=>({props:{color:t},style:{[`&.${an.checked}`]:{color:(e.vars||e).palette[t].main,"&:hover":{backgroundColor:e.alpha((e.vars||e).palette[t].main,(e.vars||e).palette.action.hoverOpacity),"@media (hover: none)":{backgroundColor:"transparent"}},[`&.${an.disabled}`]:{color:e.vars?e.vars.palette.Switch[`${t}DisabledColor`]:`${"light"===e.palette.mode?e.lighten(e.palette[t].main,.62):e.darken(e.palette[t].main,.55)}`}},[`&.${an.checked} + .${an.track}`]:{backgroundColor:(e.vars||e).palette[t].main},...e.focusVisible&&{[`&.${an.checked} + .${an.track}`]:{backgroundColor:e.alpha((e.vars||e).palette[t].main,.5)},[`&.${an.disabled} + .${an.track}`]:{backgroundColor:e.alpha(e.vars?e.vars.palette.common.onBackground:`${"light"===e.palette.mode?e.palette.common.black:e.palette.common.white}`,e.vars?e.vars.opacity.switchTrackDisabled:`${"light"===e.palette.mode?.12:.2}`)},[`&.${an.checked}.${an.disabled} + .${an.track}`]:{backgroundColor:e.alpha((e.vars||e).palette[t].main,e.vars?e.vars.opacity.switchTrackDisabled:`${"light"===e.palette.mode?.12:.2}`)}}}}))]}))),ad=(0,m.styled)("span",{name:"MuiSwitch",slot:"Track"})((0,w.default)(({theme:e})=>({height:"100%",width:"100%",borderRadius:7,zIndex:-1,...(0,q.getTransitionStyles)(e,["opacity","background-color"],{duration:e.transitions.duration.shortest}),"@media (forced-colors: active)":{boxSizing:"border-box",border:"1px solid ButtonBorder"},...e.focusVisible?{backgroundColor:e.alpha(e.vars?e.vars.palette.common.onBackground:`${"light"===e.palette.mode?e.palette.common.black:e.palette.common.white}`,e.vars?e.vars.opacity.switchTrack:`${"light"===e.palette.mode?.38:.3}`)}:{backgroundColor:e.vars?e.vars.palette.common.onBackground:`${"light"===e.palette.mode?e.palette.common.black:e.palette.common.white}`,opacity:e.vars?e.vars.opacity.switchTrack:`${"light"===e.palette.mode?.38:.3}`}}))),ac=(0,m.styled)("span",{name:"MuiSwitch",slot:"Thumb"})((0,w.default)(({theme:e})=>({boxShadow:(e.vars||e).shadows[1],backgroundColor:"currentColor",boxSizing:"border-box",border:"1px solid transparent",width:20,height:20,borderRadius:"50%"}))),au=r.forwardRef(function(e,t){let a=(0,f.useDefaultProps)({props:e,name:"MuiSwitch"}),{className:n,color:i="primary",edge:r=!1,size:s="medium",sx:l,slots:o={},slotProps:d={},...c}=a,h={...a,color:i,edge:r,size:s},m=(e=>{let{classes:t,edge:a,size:n,color:i,checked:r,disabled:s}=e,l={root:["root",a&&`edge${(0,em.default)(a)}`,`size${(0,em.default)(n)}`],switchBase:["switchBase",`color${(0,em.default)(i)}`,r&&"checked",s&&"disabled"],thumb:["thumb"],track:["track"],input:["input"]},o=(0,p.default)(l,aa,t);return{...t,...o}})(h),g=d.input,b={slots:o,slotProps:d},[w,x]=(0,ew.default)("root",{className:(0,u.default)(m.root,n),elementType:al,externalForwardedProps:b,ownerState:h,additionalProps:{sx:l}}),[k,y]=(0,ew.default)("thumb",{className:m.thumb,elementType:ac,externalForwardedProps:b,ownerState:h}),_=(0,v.jsx)(k,{...y}),[C,S]=(0,ew.default)("track",{className:m.track,elementType:ad,externalForwardedProps:b,ownerState:h});return(0,v.jsxs)(w,{...x,children:[(0,v.jsx)(ao,{type:"checkbox",icon:_,checkedIcon:_,ref:t,ownerState:h,...c,classes:{...m,root:m.switchBase},slots:{...o.switchBase&&{root:o.switchBase},...o.input&&{input:o.input}},slotProps:{...d.switchBase&&{root:"function"==typeof d.switchBase?d.switchBase(h):d.switchBase},input:(0,as.mergeSlotProps)("function"==typeof g?g(h):g,{role:"switch"})}}),(0,v.jsx)(C,{...S})]})});var ap=e.i(80408),ah=e.i(6971),am=e.i(4472),af=e.i(33105),ag=e.i(57769),ab=e.i(70676),aw=e.i(5573),ax=e.i(64537),av=e.i(50458),ak=e.i(97762),ay=e.i(10874),a_=e.i(28754),aC=e.i(19344),aS=e.i(26217),aj=e.i(72465),aT=e.i(45244),aE=e.i(17248);function aN(){return(0,i.jsxs)("div",{"aria-hidden":"true",style:{display:"none"},children:[(0,i.jsxs)(t0.default,{children:[(0,i.jsx)(t7.default,{expandIcon:(0,i.jsx)(eC.default,{}),children:"Basic Accordion"}),(0,i.jsx)(t3.default,{children:(0,i.jsx)(g.default,{variant:"body1",children:"Accordion Details"})}),(0,i.jsxs)(t4,{children:[(0,i.jsx)(R.default,{children:"Cancel"}),(0,i.jsx)(R.default,{color:"primary",children:"Action"})]})]}),(0,i.jsx)(R.default,{size:"small",children:"Text"}),(0,i.jsx)(R.default,{size:"medium",children:"Text"}),(0,i.jsx)(R.default,{size:"large",children:"Text"}),(0,i.jsx)(R.default,{size:"small",disabled:!0,children:"Text"}),(0,i.jsx)(R.default,{size:"medium",disabled:!0,children:"Text"}),(0,i.jsx)(R.default,{size:"large",disabled:!0,children:"Text"}),(0,i.jsx)(R.default,{variant:"outlined",size:"small",startIcon:(0,i.jsx)(ag.default,{}),children:"Outlined"}),(0,i.jsx)(R.default,{variant:"outlined",size:"medium",startIcon:(0,i.jsx)(ag.default,{}),children:"Outlined"}),(0,i.jsx)(R.default,{variant:"outlined",size:"large",startIcon:(0,i.jsx)(ag.default,{}),children:"Outlined"}),(0,i.jsx)(R.default,{variant:"outlined",size:"small",disabled:!0,children:"Outlined"}),(0,i.jsx)(R.default,{variant:"outlined",size:"medium",disabled:!0,children:"Outlined"}),(0,i.jsx)(R.default,{variant:"outlined",size:"large",disabled:!0,children:"Outlined"}),(0,i.jsx)(R.default,{variant:"contained",size:"small",endIcon:(0,i.jsx)(ab.default,{}),children:"Contained"}),(0,i.jsx)(R.default,{variant:"contained",size:"medium",endIcon:(0,i.jsx)(ab.default,{}),children:"Contained"}),(0,i.jsx)(R.default,{variant:"contained",size:"large",endIcon:(0,i.jsx)(ab.default,{}),children:"Contained"}),(0,i.jsx)(R.default,{variant:"contained",size:"small",disabled:!0,children:"Contained"}),(0,i.jsx)(R.default,{variant:"contained",size:"medium",disabled:!0,children:"Contained"}),(0,i.jsx)(R.default,{variant:"contained",size:"large",disabled:!0,children:"Contained"}),(0,i.jsxs)(eR.default,{size:"small",children:[(0,i.jsx)(R.default,{children:"One"}),(0,i.jsx)(R.default,{children:"Two"})]}),(0,i.jsxs)(eR.default,{children:[(0,i.jsx)(R.default,{children:"One"}),(0,i.jsx)(R.default,{children:"Two"})]}),(0,i.jsxs)(eR.default,{size:"large",children:[(0,i.jsx)(R.default,{children:"One"}),(0,i.jsx)(R.default,{children:"Two"})]}),(0,i.jsxs)(eR.default,{variant:"text",size:"small",children:[(0,i.jsx)(R.default,{children:"One"}),(0,i.jsx)(R.default,{children:"Two"})]}),(0,i.jsxs)(eR.default,{variant:"text",children:[(0,i.jsx)(R.default,{children:"One"}),(0,i.jsx)(R.default,{children:"Two"})]}),(0,i.jsxs)(eR.default,{variant:"text",size:"large",children:[(0,i.jsx)(R.default,{children:"One"}),(0,i.jsx)(R.default,{children:"Two"})]}),(0,i.jsxs)(ts.default,{children:[(0,i.jsxs)(tl.default,{children:[(0,i.jsx)(g.default,{variant:"h5",component:"h2",children:"Card"}),(0,i.jsx)(g.default,{variant:"body2",color:"textSecondary",component:"p",children:"Card content"})]}),(0,i.jsx)(t5.default,{children:(0,i.jsx)(R.default,{variant:"outlined",children:"Action"})})]}),(0,i.jsx)(t6.default,{label:"Checkbox",control:(0,i.jsx)(t8.default,{})}),(0,i.jsx)(t6.default,{label:"Checkbox",control:(0,i.jsx)(t8.default,{checked:!0})}),(0,i.jsx)(t6.default,{label:"Checkbox",control:(0,i.jsx)(t8.default,{disabled:!0})}),(0,i.jsx)(t6.default,{label:"Radio",control:(0,i.jsx)(t9.default,{})}),(0,i.jsx)(t6.default,{label:"Radio",control:(0,i.jsx)(t9.default,{checked:!0})}),(0,i.jsx)(t6.default,{label:"Radio",control:(0,i.jsx)(t9.default,{disabled:!0})}),(0,i.jsx)(t6.default,{label:"Switch",control:(0,i.jsx)(au,{})}),(0,i.jsx)(t6.default,{label:"Switch",control:(0,i.jsx)(au,{checked:!0})}),(0,i.jsx)(t6.default,{label:"Switch",control:(0,i.jsx)(au,{disabled:!0})}),(0,i.jsx)(z.default,{color:"primary",size:"small",children:(0,i.jsx)(am.default,{})}),(0,i.jsx)(z.default,{color:"primary",children:(0,i.jsx)(am.default,{fontSize:"small"})}),(0,i.jsx)(z.default,{color:"primary",children:(0,i.jsx)(am.default,{})}),(0,i.jsx)(z.default,{color:"primary",children:(0,i.jsx)(am.default,{fontSize:"large"})}),(0,i.jsx)(z.default,{disabled:!0,children:(0,i.jsx)(am.default,{})}),(0,i.jsxs)(ah.default,{size:"small",value:"one",exclusive:!0,children:[(0,i.jsx)(ap.default,{value:"one",children:"One"}),(0,i.jsx)(ap.default,{value:"two",children:"Two"})]}),(0,i.jsxs)(ah.default,{size:"small",value:"two",exclusive:!0,children:[(0,i.jsx)(ap.default,{value:"one",children:"One"}),(0,i.jsx)(ap.default,{value:"two",children:"Two"})]}),(0,i.jsxs)(ah.default,{size:"medium",value:"one",exclusive:!0,children:[(0,i.jsx)(ap.default,{value:"one",children:"One"}),(0,i.jsx)(ap.default,{value:"two",children:"Two"})]}),(0,i.jsxs)(ah.default,{size:"medium",value:"two",exclusive:!0,children:[(0,i.jsx)(ap.default,{value:"one",children:"One"}),(0,i.jsx)(ap.default,{value:"two",children:"Two"})]}),(0,i.jsxs)(ah.default,{size:"large",value:"one",exclusive:!0,children:[(0,i.jsx)(ap.default,{value:"one",children:"One"}),(0,i.jsx)(ap.default,{value:"two",children:"Two"})]}),(0,i.jsxs)(ah.default,{size:"large",value:"two",exclusive:!0,children:[(0,i.jsx)(ap.default,{value:"one",children:"One"}),(0,i.jsx)(ap.default,{value:"two",children:"Two"})]}),(0,i.jsx)(eP.default,{title:"A basic tooltip",children:(0,i.jsx)(z.default,{"aria-label":"tooltip",children:(0,i.jsx)(af.default,{})})}),(0,i.jsx)(F.default,{href:"#",children:"Link"}),(0,i.jsx)(P.default,{}),(0,i.jsx)(g.default,{variant:"h1",children:"Heading"}),(0,i.jsx)(g.default,{variant:"h2",children:"Heading"}),(0,i.jsx)(g.default,{variant:"h3",children:"Heading"}),(0,i.jsx)(g.default,{variant:"h4",children:"Heading"}),(0,i.jsx)(g.default,{variant:"h5",children:"Heading"}),(0,i.jsx)(g.default,{variant:"h6",children:"Heading"}),(0,i.jsx)(g.default,{variant:"subtitle1",children:"Subtitle"}),(0,i.jsx)(g.default,{variant:"subtitle2",children:"Subtitle"}),(0,i.jsx)(g.default,{variant:"body1",children:"Body"}),(0,i.jsx)(g.default,{variant:"body2",children:"Body"}),(0,i.jsx)(g.default,{variant:"button",children:"Button Text"}),(0,i.jsx)(g.default,{variant:"caption",children:"Caption"}),(0,i.jsx)(g.default,{variant:"overline",children:"Overline"}),(0,i.jsxs)(aT.default,{value:0,indicatorColor:"primary",textColor:"primary",children:[(0,i.jsx)(aj.default,{label:"Tab One"}),(0,i.jsx)(aj.default,{label:"Tab Two"}),(0,i.jsx)(aj.default,{label:"Disabled",disabled:!0})]}),(0,i.jsx)(F.default,{href:"#",children:"Standard Link"}),(0,i.jsxs)(ay.default,{children:[(0,i.jsx)(a_.default,{children:(0,i.jsx)(aC.default,{primary:"List Item",secondary:"Secondary text"})}),(0,i.jsxs)(a_.default,{button:!0,children:[(0,i.jsx)(eI.default,{children:(0,i.jsx)(eS.default,{})}),(0,i.jsx)(aC.default,{primary:"Clickable List Item"})]}),(0,i.jsx)(a_.default,{disabled:!0,children:(0,i.jsx)(aC.default,{primary:"Disabled List Item"})})]}),(0,i.jsx)(aE.default,{label:"Text Field",variant:"outlined"}),(0,i.jsx)(aE.default,{label:"Disabled Field",variant:"outlined",disabled:!0}),(0,i.jsx)(aE.default,{label:"Error Field",variant:"outlined",error:!0,helperText:"There is an error."}),(0,i.jsx)(aE.default,{label:"Multiline Field",variant:"outlined",multiline:!0,rows:3}),(0,i.jsxs)(ax.default,{variant:"outlined",children:[(0,i.jsx)(ak.default,{children:"Sample Type"}),(0,i.jsxs)(aS.default,{value:"",label:"Sample Type",children:[(0,i.jsx)(eD.default,{value:"",children:"None"}),(0,i.jsx)(eD.default,{value:"one",children:"One"}),(0,i.jsx)(eD.default,{value:"two",children:"Two"})]}),(0,i.jsx)(av.default,{children:"Select a sample type"})]}),(0,i.jsx)(aw.default,{label:"Default Chip"}),(0,i.jsx)(aw.default,{label:"Primary Chip",color:"primary"}),(0,i.jsx)(aw.default,{label:"Outlined Chip",color:"primary",variant:"outlined"})]})}e.s(["default",0,function(){let[e,t]=(0,r.useState)(null),[a,n]=(0,r.useState)([]),[s,l]=(0,r.useState)([]),[o,d]=(0,r.useState)("");return(0,r.useEffect)(()=>{(async()=>{let e=window.location.origin,a=window.location.pathname.split("/").slice(0,-1).join("/").split("/").filter(Boolean),n="/";for(let t=1;t<=a.length;t+=1){let i=`/${a.slice(0,t).join("/")}/neon-react`,r=new XMLHttpRequest;if(r.open("HEAD",`${e}${i}/biorepo_lib/getSymbiniConfig.php`,!1),r.send(),200===r.status){n=`/${a.slice(0,t).join("/")}`;break}r.status}let i=`${e}${n}/neon-react/biorepo_lib/getSymbiniConfig.php`;try{let e=await fetch(i);if(!e.ok)throw Error("Failed to fetch configuration");let a=await e.json();t(a)}catch(e){console.error("Error fetching configuration:",e)}})()},[]),(0,r.useEffect)(()=>{if(!e)return;let t=async()=>{try{let t=await fetch(`${e.CLIENT_ROOT}/neon-react/biorepo_lib/sidebar.json?ts=${Date.now()}`);if(!t.ok)throw Error("Failed to fetch sidebar links");let a=await t.json(),{pathname:n}=window.location,i=a[n.split("/").pop()]||a.default;l(i.links),d(i.sidebarTitle)}catch(e){console.error("Error fetching sidebar links:",e)}};(async()=>{try{let t=await fetch(`${e.CLIENT_ROOT}/neon-react/biorepo_lib/breadcrumbs.json?ts=${Date.now()}`);if(!t.ok)throw Error("Failed to fetch breadcrumbs");let a=(await t.json())[window.location.pathname.slice(e.CLIENT_ROOT.length)]||[{name:"Data & Samples",href:"https://www.neonscience.org/data-samples"},{name:"Sample Portal"}];n(a),document.querySelector('nav[data-selenium="neon-page.breadcrumbs"]'),document.getElementById("innertext")}catch(e){console.error("Error fetching breadcrumbs:",e)}})(),t()},[e]),(0,r.useEffect)(()=>{document.querySelector('div[data-selenium="neon-page.content"]')&&window.dispatchEvent(new CustomEvent("biorepo:neon-page-ready"))},[]),(0,i.jsx)(tJ,{breadcrumbs:a,breadcrumbHomeHref:"https://www.neonscience.org/",sidebarLinks:s,sidebarTitle:o,children:(0,i.jsx)(aN,{})})}],22906)}]);

//# sourceMappingURL=1gvkylarq2vfb.js.map