<?php

return [

    //index
    'people_list_number_kids' => ':count ребёнок|:count ребёнка|:count детей',
    'people_list_last_updated' => 'Последнее обновление:',
    'people_list_number_reminders' => ':count напоминание|:count напоминания|:count напоминаний',
    'people_list_blank_title' => 'Вы пока ни кого ещё не добавили',
    'people_list_blank_cta' => 'Добавить кого нибудь',
    'people_list_stats' => ':count contacts',
    'people_list_sort' => 'Sort',
    'people_list_firstnameAZ' => 'Сортировать по имени А → Я',
    'people_list_firstnameZA' => 'Сортировать по имени Я → А',
    'people_list_lastnameAZ' => 'Сортировать по фамилии А → Я',
    'people_list_lastnameZA' => 'Сортировать по фамилии Я → А',
    'people_list_filter_tag' => 'Showing all the contacts tagged with <span class="pretty-tag">:name</span>',
    'people_list_clear_filter' => 'Clear filter',
    'people_list_contacts_per_tags' => '{0} 0 contact|{1,1} 1 contact|{2,*} :count contacts',
    'people_search' => 'Search your contacts...',
    'people_search_no_results' => 'No relevant contacts found :(',

    // people add
    'people_add_title' => 'Добавить человека',
    'people_add_firstname' => 'Имя',
    'people_add_middlename' => 'Отчество (не обязательно)',
    'people_add_lastname' => 'Фамилия (не обязательно)',
    'people_add_cta' => 'Добавить',
    'people_add_gender' => 'Пол',
    'people_delete_success' => 'Контакт был удалён',
    'people_delete_message' => 'Если вам нужно удалить этот контакт,',
    'people_delete_click_here' => 'нажмите сюда',
    'people_delete_confirmation' => 'Вы уверены что хотите удалить этот контакт? Восстановление невозможно.',
    'people_add_birthday_reminder' => 'Wish happy birthday to :name',
    'people_add_import' => 'Do you want to <a href="/settings/import">import your contacts</a>?',

    // show
    'section_personal_information' => 'Личные данные',
    'section_personal_activities' => 'Активности',
    'section_personal_reminders' => 'Напоминания',
    'section_personal_tasks' => 'Задачи',
    'section_personal_gifts' => 'Подарки',

    //
    'link_to_list' => 'Список людей',

    // Header
    'edit_contact_information' => 'Редактировать контакты',
    'call_button' => 'Log a call',

    // Calls
    'modal_call_title' => 'Log a call',
    'modal_call_comment' => 'What did you talk about? (optional)',
    'modal_call_date' => 'The phone call happened earlier today.',
    'modal_call_change' => 'Change',
    'modal_call_exact_date' => 'The phone call happened on',
    'calls_add_success' => 'The phone call has been saved.',
    'call_delete_confirmation' => 'Are you sure you want to delete this call?',
    'call_delete_success' => 'The call has been deleted successfully',
    'call_title' => 'Phone calls',
    'call_empty_comment' => 'No details',
    'call_blank_title' => 'Keep track of the phone calls you\'ve done with :name',
    'call_blank_desc' => 'You called :name',

    // age - birthday
    'birthdate_not_set' => 'День рождения не указан',
    'age_approximate_in_years' => 'примерно :age лет',
    'age_exact_in_years' => ':age лет',
    'age_exact_birthdate' => 'день рожнения: :date',

    // Last called
    'last_called' => 'Последний звонок: :date',
    'last_called_empty' => 'Последний звонок: неизвестно',
    'last_activity_date' => 'Последняя активность вместе: :date',
    'last_activity_date_empty' => 'Последняя активность вместе: неизвестно',

    // additional information
    'information_edit_success' => 'Профиль был успешно обновлён',
    'information_edit_title' => 'Редактировать данные :name',
    'information_edit_avatar' => 'Фото/Аватар контакта',
    'information_edit_max_size' => 'Макс :size Мб.',
    'information_edit_firstname' => 'Имя',
    'information_edit_lastname' => 'Фамилия (не обязательно)',
    'information_edit_facebook' => 'Facebook profile (optional)',
    'information_edit_twitter' => 'Twitter profile (optional)',
    'information_edit_linkedin' => 'LinkedIn profile (optional)',
    'information_edit_street' => 'Улица (не обязательно)',
    'information_edit_province' => 'Область (не обязательно)',
    'information_edit_postalcode' => 'Индекс (не обязательно)',
    'information_edit_city' => 'Город (не обязательно)',
    'information_edit_country' => 'Страна (не обязательно)',
    'information_edit_email' => 'Email (не обязательно)',
    'information_edit_phone' => 'Номер телефона (не обязательно)',
    'information_edit_probably' => 'Этому человеку примерно',
    'information_edit_probably_yo' => 'лет',
    'information_edit_exact' => 'Я знаю точную дату рождения этого человека, которая',
    'information_edit_help' => 'Если вы укажите точную дату рождения для этого человека, мы создадим для вас напоминание, которое будет сообщать ежегодно о предстоящем дне рождения',
    'information_no_address_defined' => 'Адрес не указан',
    'information_no_email_defined' => 'Email не указан',
    'information_no_phone_defined' => 'Номер телефона не указан',
    'information_no_facebook_defined' => 'No Facebook defined',
    'information_no_twitter_defined' => 'No Twitter defined',
    'information_no_linkedin_defined' => 'No LinkedIn defined',
    'information_no_work_defined' => 'No work information defined',
    'information_work_at' => 'at :company',
    'work_add_cta' => 'Update work information',
    'work_edit_success' => 'Work information have been updated with success',
    'work_edit_title' => 'Update :name\'s job information',
    'work_edit_job' => 'Job title (optional)',
    'work_edit_company' => 'Company (optional)',

    // food preferencies
    'food_preferencies_add_success' => 'Предпочтения в еде были сохранены',
    'food_preferencies_edit_description' => 'Возможно у :firstname или кого-то из его(её) семьи есть аллергия. Или не любит какой-то определённый продукт. Запишите это и в следующий раз когда вы будете кушать вместе вы вспомните об этом.',
    'food_preferencies_edit_description_no_last_name' => 'Возможно у :firstname или кого-то из её семьи есть аллергия. Или не любит какой-то определённый продукт. Запишите это и в следующий раз когда вы будете кушать вместе вы вспомните об этом.',
    'food_preferencies_edit_title' => 'Укажите предпочтения в еде',
    'food_preferencies_edit_cta' => 'Сохранить предпочтения в еде',
    'food_preferencies_title' => 'Предпочтения в еде',
    'food_preferencies_cta' => 'Добавить предпочтения в еде',

    // reminders
    'reminders_blank_title' => 'Есть ли что-то связанное с :name, о чём вы хотите получить напоминание?',
    'reminders_blank_add_activity' => 'Добавить напоминание',
    'reminders_add_title' => 'О чём, связанном с :name, вам напомнить?',
    'reminders_add_description' => 'Напомнить о:',
    'reminders_add_next_time' => 'Когда в следующий раз вы хотите получить напоминание?',
    'reminders_add_once' => 'Напомнить один раз',
    'reminders_add_recurrent' => 'Повторять напоминание с периодичностью: ',
    'reminders_add_starting_from' => 'начиная с даты указанной выше',
    'reminders_add_cta' => 'Добавить напоминание',
    'reminders_add_error_custom_text' => 'Вы должны указать текст для этого напоминания',
    'reminders_create_success' => 'Напоминание было добавлено',
    'reminders_delete_success' => 'Напоминание было удалено',

    'reminder_frequency_week' => 'каждую :number неделю|каждые :number недели|каждые :number недель',
    'reminder_frequency_month' => 'каждый :number месяц|каждые :number месяца|каждые :number месяцев',
    'reminder_frequency_year' => 'каждый :number год|каждые :number года|каждые :number лет',
    'reminder_frequency_one_time' => 'в :date',
    'reminders_delete_confirmation' => 'Вы уверены что хотите удалить это напоминание?',
    'reminders_delete_cta' => 'Удалить',
    'reminders_next_expected_date' => 'в',
    'reminders_cta' => 'Добавить напоминание',
    'reminders_description' => 'По каждому из напоминаний выше мы отправим вам письмо. Они высылаются по утрам',
    'reminders_one_time' => 'один раз',
    'reminders_type_week' => 'неделя',
    'reminders_type_month' => 'месяц',
    'reminders_type_year' => 'год',
    'reminders_birthday' => 'Birthdate of :name',

    // significant other
    'significant_other_sidebar_title' => 'Вторая половинка',
    'significant_other_cta' => 'Добавить вторую половинку',
    'significant_other_add_title' => 'Кто вторая половинка :name?',
    'significant_other_add_firstname' => 'Имя',
    'significant_other_add_unknown' => 'Я не знаю возраст',
    'significant_other_add_probably' => 'Этой личности примерно',
    'significant_other_add_probably_yo' => 'лет',
    'significant_other_add_exact' => 'Я знаю точную дату рождения и она:',
    'significant_other_add_help' => 'Если вы укажите точную дату рождения для этого человека, мы создадим для вас напоминание, которое будет сообщать ежегодно о предстоящем дне рождения',
    'significant_other_add_cta' => 'Добавить вторую половинку',
    'significant_other_edit_cta' => 'Редактировать вторую половинку',
    'significant_other_delete_confirmation' => 'Вы уверены что хотите удалить эту вторую половинку? Восстановление невозможно.',
    'significant_other_unlink_confirmation' => 'Are you sure you want to delete this relationship? This significant other will not be deleted - only the relationship between the two.',
    'significant_other_add_success' => 'Вторая половинка была успешно добавлена',
    'significant_other_edit_success' => 'Вторая половинка была успешно обновлена',
    'significant_other_delete_success' => 'Вторая половинка была успешно удалена',
    'significant_other_add_birthday_reminder' => 'Поздравьте с днём рождения :name, вторую половинку :contact_firstname',
    'significant_other_add_person' => 'Add a new person',
    'significant_other_link_existing_contact' => 'Link existing contact',
    'significant_other_add_no_existing_contact' => 'You don\'t have any contacts who can be :name\'s significant others at the moment.',
    'significant_other_add_existing_contact' => 'Select an existing contact as the significant other for :name',
    'contact_add_also_create_contact' => 'Create a Contact entry for this person.',
    'contact_add_add_description' => 'This will let you treat this significant other like any other contact.',

    // kids
    'kids_sidebar_title' => 'Дети',
    'kids_sidebar_cta' => 'Добавить ещё одного ребёнка',
    'kids_blank_cta' => 'Добавить ребёнка',
    'kids_add_title' => 'Добавить ребёнка',
    'kids_add_boy' => 'Мальчик',
    'kids_add_girl' => 'Девочка',
    'kids_add_gender' => 'Пол',
    'kids_add_firstname' => 'Имя',
    'kids_add_firstname_help' => 'Мы предполагаем что имя: :name',
    'kids_add_lastname' => 'Last name (optional)',
    'kids_add_also_create' => 'Also create a Contact entry for this person.',
    'kids_add_also_desc' => 'This will let you treat this kid like any other contact.',
    'kids_add_no_existing_contact' => 'You don\'t have any contacts who can be :name\'s kid at the moment.',
    'kids_add_existing_contact' => 'Select an existing contact as the kid for :name',
    'kids_add_probably' => 'Этому ребёнку вероятно',
    'kids_add_probably_yo' => 'лет',
    'kids_add_exact' => 'Я знаю точную дату рождения и она:',
    'kids_add_help' => 'Если вы укажите точную дату рождения для этого ребёнка, мы создадим для вас напоминание, которое будет сообщать ежегодно о предстоящем дне рождения',
    'kids_add_cta' => 'Добавить ребёнка',
    'kids_edit_title' => 'Изменить информацию о :name',
    'kids_delete_confirmation' => 'Вы уверены что хотите удалить запись об этом ребёнке? Восстановление невозможно.',
    'kids_add_success' => 'Запись о ребёнке была успешно добавлена!',
    'kids_update_success' => 'Запись о ребёнке была успешно обновлена!',
    'kids_delete_success' => 'Запись о ребёнке была удалена!',
    'kids_add_birthday_reminder' => 'Поздравьте с днём рождения :name, ребёнка :contact_firstname',
    'kids_unlink_confirmation' => 'Are you sure you want to delete this relationship? This kid will not be deleted - only the relationship between the two.',

    // tasks
    'tasks_desc' => 'Управляйте Задачами связанными с :name',
    'tasks_blank_title' => 'Похоже что у вас пока нет задач связанных с :name',
    'tasks_blank_add_activity' => 'Добавить задачу',
    'tasks_add_title_page' => 'Добавить новую задачу по :name',
    'tasks_add_title' => 'Название',
    'tasks_add_optional_comment' => 'Комментарий (не обязательно)',
    'tasks_add_cta' => 'Добавить задачу',
    'tasks_add_success' => 'Задача была успешно добавлена',
    'tasks_delete' => 'Удалить',
    'tasks_reactivate' => 'Reactivate',
    'tasks_mark_complete' => 'Выполнено',
    'tasks_add_task' => 'Добавить задачу',
    'tasks_added_on' => 'дата создания: :date',
    'tasks_delete_confirmation' => 'Вы уверены что хотите удалить эту задачу?',
    'tasks_delete_success' => 'Задача была усрешна удалена',
    'tasks_complete_success' => 'Статус задачи был изменён',

    // activities
    'activity_title' => 'Активности',
    'activity_type_group_simple_activities' => 'Простые',
    'activity_type_group_sport' => 'Спорт',
    'activity_type_group_food' => 'Еда',
    'activity_type_group_cultural_activities' => 'Культурные',
    'activity_type_just_hung_out' => 'просто повеселились',
    'activity_type_watched_movie_at_home' => 'смотрели кино дома',
    'activity_type_talked_at_home' => 'разговаривали дома',
    'activity_type_did_sport_activities_together' => 'занимались спортом вместе',
    'activity_type_ate_at_his_place' => 'ели в его месте',
    'activity_type_ate_at_her_place' => 'ели в её месте',
    'activity_type_went_bar' => 'отправились в бар',
    'activity_type_ate_at_home' => 'ели дома',
    'activity_type_picknicked' => 'пикник',
    'activity_type_went_theater' => 'ходили в театр',
    'activity_type_went_concert' => 'ходили на концерт',
    'activity_type_went_play' => 'ходили играть',
    'activity_type_went_museum' => 'были в музее',
    'activity_type_ate_restaurant' => 'ели в ресторане',
    'activities_add_activity' => 'Добавить активность',
    'activities_more_details' => 'Больше подробностей',
    'activities_hide_details' => 'Скрыть подробносвти',
    'activities_delete_confirmation' => 'Вы уверены что хотите удалить эту активность?',
    'activities_item_information' => ':Activity. Дата: :date',
    'activities_add_title' => 'Что вы делали с :name?',
    'activities_summary' => 'Опишите что вы делали',
    'activities_add_pick_activity' => '(Не обязательно) Вы хотите классифицировать эту активность? Это не обязательно, но это даст вам статистику позже',
    'activities_add_date_occured' => 'Дата когда это произошло',
    'activities_add_optional_comment' => 'Комментарий (не обязательно)',
    'activities_add_cta' => 'Записать активность',
    'activities_blank_title' => 'Следите за тем, что вы делали с :name в прошлом, и о чем вы говорили',
    'activities_blank_add_activity' => 'Добавить активность',
    'activities_add_success' => 'Активность была добавлена',
    'activities_update_success' => 'Активность была обновлена',
    'activities_delete_success' => 'Активность была удалена',

    // notes
    'notes_create_success' => 'Заметка была добавлена',
    'notes_update_success' => 'The note has been saved successfully',
    'notes_delete_success' => 'Заметка была удалена',
    'notes_add_title' => 'Добавить заметку о :name',
    'notes_add_cta' => 'Добавить заметку',
    'notes_edit_title' => 'Edit note about :name',
    'notes_edit_cta' => 'Save note',
    'notes_written_on' => 'Дата создания: :date',
    'notes_add_one_more' => 'Добавить заметку',
    'notes_title' => 'Заметки',
    'notes_blank_link' => 'Добавить заметку',
    'notes_blank_name' => 'о :name',
    'notes_delete_confirmation' => 'Вы уверены что хотите удалить эту заметку? Восстановление невозможно.',

    // gifts
    'gifts_blank_title' => 'Сохраняйте идеи подарков для :name',
    'gifts_blank_add_gift' => 'Добавить подарок',
    'gifts_add_success' => 'Подарок был добавлен',
    'gifts_delete_success' => 'Подарок был удалён',
    'gifts_delete_confirmation' => 'Вы уверены что хотите удалить этот подарок?',
    'gifts_add_gift' => 'Добавить подарок',
    'gifts_link' => 'Ссылка',
    'gifts_added_on' => 'Дата создания: :date',
    'gifts_delete_cta' => 'Удалить',
    'gifts_offered' => 'offered',
    'gifts_add_title' => 'Управление подарками для :name',
    'gifts_add_gift_idea' => 'Идея подарка',
    'gifts_add_gift_already_offered' => 'Подарок уже предложен',
    'gifts_add_gift_title' => 'Что это за подарок?',
    'gifts_add_link' => 'Ссылка на веб-страницу (не обязательно)',
    'gifts_add_value' => 'Стоимость (не обязательно)',
    'gifts_add_comment' => 'Комментарий (не обязательно)',
    'gifts_add_someone' => 'Этот подарок в том числе для кого-то из семьи :name',
    'gifts_add_cta' => 'Добавить',
    'gifts_gift_idea' => 'Идеи',
    'gifts_gift_already_offered' => 'Подаренные',
    'gifts_table_date_added' => 'Дата создания',
    'gifts_table_description' => 'Описание',
    'gifts_table_actions' => 'Действия',

    // debts
    'debt_delete_confirmation' => 'Вы уверены что хотите удалить этот долг?',
    'debt_delete_success' => 'Долг был удалён',
    'debt_add_success' => 'Долг был добавлен',
    'debt_title' => 'Долги',
    'debt_add_cta' => 'Добавить долг',
    'debt_you_owe' => 'Вы должны :amount',
    'debt_they_owe' => ':name должен вам :amount',
    'debt_add_title' => 'Управление долгами',
    'debt_add_you_owe' => 'Вы должны :name',
    'debt_add_they_owe' => ':name должен вам',
    'debt_add_amount' => 'сумма ',
    'debt_add_reason' => 'причина долга (не обязательно)',
    'debt_add_add_cta' => 'Добавить долг',
    'debt_edit_update_cta' => 'Update debt',
    'debt_edit_success' => 'The debt has been updated successfully',
    'debts_blank_title' => 'Manage debts you owe to :name or :name owes you',

    // tags
    'tag_edit' => 'Edit tag',
];
