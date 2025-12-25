<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tipos de archivo
    |--------------------------------------------------------------------------
    */

    'images_type_validate'    => 'jpg,jpeg,gif,png,svg,bmp,JPG,JPEG,GIF,PNG,SVG,BMP,XLSX,XLS,MP4,3GP,BIN,PDF,DOC,DOCX,PPTX,PPT,TXT',
    'images_type_extension'   => ['jpg','jpeg','gif','png','svg','bmp','JPG','JPEG','GIF','PNG','SVG','BMP','xlsx','xls','mp4','3gp','bin','pdfs','doc','docx','pptx','ppt','txt'],
    'videos_type_extension'   => ['mp4','3gp','bin'],
    'excel_type_extension'    => ['xlsx','xls'],
    'document_type_extension' => ['xlsx','xls','mp4','3gp','bin','pdfs','doc','docx','pptx','ppt','txt','PDF'],
    'file_dropzone_mimetype'  => 'image/jpg,image/jpeg,image/gif,image/png,image/JPG,image/JPEG,image/GIF,image/PNG,video/mp4,video/3gp,image/svg+xml',

//    'empresa_id'              => env('EMPRESA_ID',1),
    // -----------------------------------------------------------
    // Aqui se deben configurar los formatos a utilizar.
    // -----------------------------------------------------------

    'archivos'=>[
        'fmt_lista_catalogos'      => 'fmt_lista_catalogos.xlsx',
        'fmt_lista_usuarios'       => 'fmt_lista_usuarios.xlsx',
        'icono_video'              => 'icon-video.png',
    ],

    'menu_archivos'=>[
        'Solicitudes'      => 'fmt_lista_denuncias.xlsx',
    ],

    // ARCHIVOS DE IMAGENES DEL SISTEMA
    'logo_reportes_encabezado' => public_path().'/images/web/logo-0-reporte.png',
    'app_name_short'           => env('APP_NAME_SHORT',''),
    'nombre_empresa'           => env('NOMBRE_EMPRESA',''),
    'lema_empresa'             => env('LEMA_CAMPANA',''),
    'periodo_empresa'          => env('INFO_ONE',''),
    'direccion_responsable'    => env('INFO_TWO',''),
    'telefono_responsable'     => env('INFO_THREE',''),
    'web_responsable'          => env('INFO_FOUR',''),
    'nombre_software'          => env('NOMBRE_SOFTWARE',''),

    'ciudad_default'           => env('CIUDAD_DEFAULT',''),
    'municipio_default'        => env('MUNICIPIO_DEFAULT',''),
    'estado_default'           => env('ESTADO_DEFAULT',''),
    'empresa_id'               => env('EMPRESA_ID',1),

    'dias_mas_fecha_ingreso'   => env('DIAS_MAS_FECHA_INGRESO',1),
    'dias_mas_fecha_ejecucion' => env('DIAS_MAS_FECHA_EJECUCION',3),
    'dias_mas_fecha_limite'    => env('DIAS_MAS_FECHA_LIMITE',5),


    'consulta_500_items_general' => 500,
    'sas_id'                     => env("SAS_ID"),
    'modificar_fecha_ingreso'    => env('MODIFICAR_FECHA_INGRESO','NO'),
    'public_url'                 => env('PUBLIC_URL','http://localhost'),
    'pagina_web_id'              => 4,

    'recargo_en_pesos'           => env('RECARGO_EN_PESOS',100),
    'porcentaje_de_descuento'    => env('PORCENTAJE_DE_DESCUENTO',5),
    'porcentaje_de_recargo'      => env('PORCENTAJE_DE_RECARGO',5),
    'numeros_de_pago'            => [1,3,6,10],

    // -----------------------------------------------------------
    // La mayor parte de los Tablas estan configuradas aquí,
    // es en este mismo sitio donde la debes mantener forerver
    // -----------------------------------------------------------

    'table_names' => [
        'users' => [
            'users'       => 'users',
            'roles'       => 'roles',
            'permissions' => 'permissions',
            'user_address' => 'user_address',
            'user_extend' => 'user_extend',
            'categorias'  => 'categorias',
        ],
        'catalogos' => [
            'users'                  => 'users',
            'roles'                  => 'roles',
            'permissions'            => 'permissions',
            'registros_fiscales'     => 'registros_fiscales',
            'imagenes'               => 'imagenes',
            'imagene_parent'         => 'imagene_parent',
            'imagene_user'           => 'imagene_user',
            'logs'                   => 'logs',
            'servicios'              => 'servicios',
            'grupos'                 => 'grupos',
            'periodos'               => 'periodos',
            'recibos'                => 'recibos',
            'expedientes'            => 'expedientes',
            'archivosplanos'         => 'archivosplanos',
        ],

    ],

    'style' => [
        'denuncia' => "<style>
                            b { font-family: arial, sans-serif; }
                            bAzul { font-family: arial, sans-serif; color:blue; }
                            p {text-align: justify;}
                            bVerde { font-family: arial, sans-serif; color:green; }
                            bChocolate { font-family: arial, sans-serif; color:chocolate; }
                            bOrange { font-family: arial, sans-serif; color:orangered; }
                            bSelloBold { font-family: arial, sans-serif; font-weight: bold; }
                            span { font-family: arial, sans-serif; text-align: center; }
                       </style>",
    ],

    'fcm' => [
        'server_ios_key' => 'AAAAOxp8RQA:APA91bEQIojJa-3wWzgJRuLvm11c2qSJJG-oziCAYFWRyD7vkf7flyKBNZC0qEprPpIwpA-OuScGrjGQqBhp6zEDCkj0z6MhVLVEf9R9g8zSsKjowT6qghhm1fbPB9mcAxCmFiJQkcld',
        'server_android_key' => 'AAAAiq0Ovmw:APA91bHxmEEWGp3neZdJpoda9i-sNJApmJIdYuLQjKxto-ltdZRyYmCqbGNPE3VvdVKJNJd-3fMdapWpqIt4XLkYANjrW9yN5EZqCPWf7aYJU956a6Tzxv3zR1l-ql9-QXkghLwkLPz2',
        'server_android_alza' => 'AIzaSyD9jQdDKkXvAOgurJDSYtECzzCiaZSX1EM',
    ],


];
