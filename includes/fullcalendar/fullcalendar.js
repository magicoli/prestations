!function(){"use strict";jQuery.ajax({type:"post",url:ajaxurl,data:{action:"feed_events"},error:function(e){console.log(e)},success:function(e){!function(e){const r=JSON.parse(e);var t=r.resources,n=r.events,o=r.locale,a=r.resTitle;console.log(t);var s=document.getElementById("calendar");new FullCalendar.Calendar(s,{schedulerLicenseKey:"GPL-My-Project-Is-Open-Source",initialView:"resourceTimelineMonth",locale:o,header:{left:"prev,next today",center:"title",right:""},nowIndicator:!0,height:"auto",resources:t,resourceOrder:"mp_order",resourceAreaWidth:"15rem",resourceAreaHeaderContent:a,events:n,slotLabelFormat:[{weekday:"short"},{day:"numeric"}],eventPositioned(e,r){displayBookings()}}).render(),document.getElementById("calendar-placeholder").style.display="none"}(e)}})}();