!function(){"use strict";window.wp.i18n,jQuery(document).ready((function(e){jQuery.ajax({type:"post",url:ajaxurl,data:{action:"feed_events"},error:function(e){console.log(e)},success:function(t){!function(t){const o=JSON.parse(t);var n=o.resources,i=o.events,r=o.locale,a=o.resTitle;console.log(n);var d=document.getElementById("mltp-calendar"),l=new FullCalendar.Calendar(d,{schedulerLicenseKey:"GPL-My-Project-Is-Open-Source",initialView:"resourceTimelineMonth",locale:r,headerToolbar:{start:"title",center:"",end:"prevYear,prev today next,nextYear"},nowIndicator:!0,height:"auto",resources:n,resourceOrder:"mp_order",resourceAreaWidth:"15rem",resourceAreaHeaderContent:a,events:i,selectable:!0,selectHelper:!0,eventClick:function(t){const{__:__,_e:o}=wp.i18n;var n=t.event;__("Edit"),t.jsEvent.preventDefault(),e('<div id="dialog">'+n.extendedProps.modal+"</div>").dialog({modal:!0,draggable:!1,resizable:!1,open:function(){e(".ui-widget-overlay").on("click",(function(){e(this).parents("body").find(".ui-dialog-content").dialog("close")}))},hide:{effect:"fade",duration:300},show:{effect:"fade",duration:300},title:n.title,width:"auto",minWidth:560,maxWidth:e(window).width(),height:e(window).height()-e("#wpcontent").position().top,position:{my:"right top",at:"right top",of:window,within:"#wpcontent"},buttons:[]})},slotLabelFormat:[{weekday:"short"},{day:"numeric"}],eventPositioned(e,t){displayBookings()}});l.render(),document.getElementById("mltp-placeholder").style.display="none"}(t)}})}))}();