/* Aug 28, 2014 at 2:03 PM */

!function(){"undefined"==typeof RedactorPlugins&&(RedactorPlugins={}),RedactorPlugins.SmileyManager={init:function(){var a=this;smileyLoaded=!1,a.$editor.on("focus click keydown",function(){smileyLoaded||(a.$toolbar.find(".redactor_btn_smilies").click(),smileyLoaded=!0)})}}}(jQuery,this,document);