jQuery('.btn_upload').on('click',function(e){
    jQuery('.settings_content').slideDown();
    e.preventDefault();
});

jQuery('.btn_close').on('click',function(e){
    jQuery('.settings_content').slideUp();
    e.preventDefault();
});

jQuery('.popup').on('click',function(e){
    jQuery('.popup').slideUp();
    e.preventDefault();
});
