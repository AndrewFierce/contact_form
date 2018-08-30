/* Проверка на корректность введенных данных */

jQuery("#contactForm").validator().on("submit", function (event) {
    if (event.isDefaultPrevented()) {
        // в случае некорректно заполненной формы
        formError();
        submitMSG(false, "Пожалуйста, проверьте корректность введенных данных!");
    } else {
        // если все поля заполнены праильно
        event.preventDefault();
        submitForm();
    }
});

/* Обновление капчи */
jQuery("#reload-captcha").click(function() {
  $('#img-captcha').attr('src', '../php/captcha.php?id='+Math.random()+'');
});

//Формирование и отправка формы на php
function submitForm(){
    // Формирование пеерменных на основе полученной формы
    var name = jQuery("#name").val();       //имя
    var email = jQuery("#email").val();     //Почта
    var message = jQuery("#message").val(); //Сообщение
    var captcha = jQuery("#text-captcha").val();    //Капча

    jQuery.ajax({
        type: "POST",       //Передача данный на сервер методом POST
        url: "php/form-process.php",    //URL PHP файла для дальнейшей обработки данных
        data: "name=" + name + "&email=" + email + "&message=" + message + "&captcha=" + captcha,   //Данные для отправки на сервер
        success : function(text){   
            //в случае успешного завершения запроса...
            if (text == "success"){
                //В случае успешной отправки сообщения и отсутствия ошибок со стороны сервера формируем благодарственную форму
                formSuccess(name);
            } else {
                //В случае ошибок, оповещаем пользователя
                formError();
                submitMSG(false,text);
            }
        }
    });
}

function formSuccess(name){
    jQuery("#contactForm")[0].reset();
    submitMSG(true, "Спасибо, " + name);
}

function formError(){
    jQuery("#contactForm").removeClass().addClass('shake animated').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
        jQuery(this).removeClass();
    });
}

function submitMSG(valid, msg){
    if(valid){
        var msgClasses = "h3 text-center tada animated text-success";
        jQuery( "#contactForm" ).replaceWith( jQuery("#contactForm").addClass(msgClasses).text(msg) );
    } else {
        var msgClasses = "h3 text-center text-danger";
        jQuery("#msgSubmit").removeClass().addClass(msgClasses).text(msg);
    }
}