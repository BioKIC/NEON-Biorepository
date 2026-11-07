(globalThis.TURBOPACK||(globalThis.TURBOPACK=[])).push(["object"==typeof document?document.currentScript:void 0,92389,e=>{"use strict";var t=e.i(57727),s=e.i(71645),i=e.i(2181),n=e.i(78512);e.s(["default",0,function(){let[e,a]=(0,s.useState)(new Set),[d,l]=(0,s.useState)(!1),u=Object.fromEntries(n.default.datasets.map(e=>[e.name,e.datasetID]));return(0,s.useEffect)(()=>{let e=document.getElementById("domains-sites-modal");if(!e)return;let t=()=>{"none"!==window.getComputedStyle(e).display?setTimeout(()=>{l(!0)},300):l(!1)},s=new MutationObserver(t);return s.observe(e,{attributes:!0,attributeFilter:["style","class"]}),t(),()=>{s.disconnect()}},[]),(0,t.jsxs)("div",{style:{width:"100%"},children:[(0,t.jsx)("style",{children:`
          .ui-widget button {
            font-size: unset !important;
          }
        `}),d&&(0,t.jsx)(i.default,{selection:"SITES",selectedItems:e,onSelectionChange:e=>{a(new Set(e.set)),setTimeout(()=>{updateChip()},0)}}),Array.from(e).map(e=>{let s=u[e];return s?(0,t.jsx)("input",{type:"checkbox",id:e,name:"datasetid",value:s,className:"child",checked:!0,readOnly:!0,hidden:!0},e):null})]})}])}]);

//# sourceMappingURL=0o5ya0xpmu_0y.js.map