"use strict";function t(n){return e=>typeof e=="string"&&n.indexOf(e)!==-1}const s="cdx",o=["default","progressive","destructive"],i=["normal","primary","quiet"],a=["medium","large"],l=["x-small","small","medium"],r=["notice","warning","error","success"],d=t(r),c=["text","search","number","email","month","password","tel","url","week","date","datetime-local","time"],u=["default","error"],y=120,m=500,p="cdx-menu-footer-item",b=Symbol("CdxTabs"),S=Symbol("CdxActiveTab"),I=Symbol("CdxFieldInputId"),T=Symbol("CdxFieldDescriptionId"),x=Symbol("CdxFieldStatus"),K=Symbol("CdxDisabled"),F="".concat(s,"-no-invert");exports.ActiveTabKey=S;exports.ButtonActions=o;exports.ButtonSizes=a;exports.ButtonWeights=i;exports.DebounceInterval=y;exports.DisabledKey=K;exports.FieldDescriptionIdKey=T;exports.FieldInputIdKey=I;exports.FieldStatusKey=x;exports.IconSizes=l;exports.LibraryPrefix=s;exports.MenuFooterValue=p;exports.NoInvertClass=F;exports.PendingDelay=m;exports.TabsKey=b;exports.TextInputTypes=c;exports.ValidationStatusTypes=u;exports.makeStringTypeValidator=t;exports.statusTypeValidator=d;