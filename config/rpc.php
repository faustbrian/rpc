<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Http\Middleware\BootServer;
use Cline\RPC\Http\Middleware\ForceJson;
use Cline\RPC\Http\Middleware\RenderThrowable;
use Cline\RPC\Protocols\JsonRpcProtocol;
use Cline\RPC\Protocols\XmlRpcProtocol;
use Cline\OpenRpc\ContentDescriptor\CursorPaginatorContentDescriptor;
use Cline\OpenRpc\Schema\CursorPaginatorSchema;
use Illuminate\Routing\Middleware\SubstituteBindings;

return [
    /*
    |--------------------------------------------------------------------------
    | RPC Protocol
    |--------------------------------------------------------------------------
    |
    | The protocol handles complete message format transformation between
    | internal representation and wire format. Each protocol defines its own
    | request/response structure and serialization.
    |
    | - JsonRpcProtocol: JSON-RPC 2.0 ({"jsonrpc":"2.0","method":"..."})
    | - XmlRpcProtocol: XML-RPC (<methodCall><methodName>...</methodName>)
    |
    | Default: JsonRpcProtocol (JSON-RPC 2.0 specification)
    |
    */

    'protocol' => JsonRpcProtocol::class,

    /*
    |--------------------------------------------------------------------------
    | RPC Method Namespaces
    |--------------------------------------------------------------------------
    |
    | Here you may define the namespaces that will be used to automatically
    | discover and load your JSON-RPC method handlers. The framework will
    | scan these namespaces to register available methods for your servers.
    |
    */

    'namespaces' => [
        /*
        |--------------------------------------------------------------------------
        | Methods Namespace
        |--------------------------------------------------------------------------
        |
        | This namespace points to where your RPC method handlers are located.
        | All classes within this namespace will be scanned and registered as
        | available JSON-RPC methods if they implement the required interface.
        |
        */

        'methods' => 'App\\Http\\Methods',
    ],
    /*
    |--------------------------------------------------------------------------
    | RPC Application Paths
    |--------------------------------------------------------------------------
    |
    | These paths are used by the package to locate various components of
    | your JSON-RPC implementation. You may customize these paths based on
    | your application's directory structure and organizational preferences.
    |
    */

    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Methods Directory
        |--------------------------------------------------------------------------
        |
        | The filesystem path to the directory containing your method handlers.
        | This should correspond to the namespace defined above and is used for
        | file discovery and auto-registration of your RPC method classes.
        |
        */

        'methods' => app_path('Http/Methods'),
    ],
    /*
    |--------------------------------------------------------------------------
    | JSON-RPC Resources
    |--------------------------------------------------------------------------
    |
    | Resources provide a transformation layer between your Eloquent models
    | and the JSON-RPC responses that are returned to your consumers. This
    | allows you to easily format and structure your response data. You may
    | register custom resource classes here that will be used throughout
    | your RPC method handlers to transform models and collections.
    |
    */

    'resources' => [
        // 'users' => \App\Http\Resources\UserResource::class,
        // 'posts' => \App\Http\Resources\PostResource::class,
    ],
    /*
    |--------------------------------------------------------------------------
    | JSON-RPC Server Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure one or more JSON-RPC servers for your application.
    | Each server can have its own unique configuration including the path,
    | middleware stack, exposed methods, and API versioning. This allows you
    | to create separate RPC endpoints for different parts of your application
    | or to version your API by running multiple servers simultaneously.
    |
    */

    'servers' => [
        [
            /*
            |--------------------------------------------------------------------------
            | Server Name
            |--------------------------------------------------------------------------
            |
            | The human-readable name for this RPC server. This will be displayed
            | in the OpenRPC specification document and helps identify the server
            | when multiple RPC endpoints are configured. Defaults to your app name.
            |
            */

            'name' => env('APP_NAME'),
            /*
            |--------------------------------------------------------------------------
            | Server Path
            |--------------------------------------------------------------------------
            |
            | The URI path where this JSON-RPC server will accept requests. All RPC
            | method calls should be sent as POST requests to this endpoint. You may
            | change this to any path that fits your application's URL structure.
            |
            */

            'path' => '/rpc',
            /*
            |--------------------------------------------------------------------------
            | Server Route Name
            |--------------------------------------------------------------------------
            |
            | The named route identifier for this server. This allows you to generate
            | URLs to the RPC endpoint using Laravel's route helper functions. Ensure
            | this value is unique across all your configured RPC servers.
            |
            */

            'route' => 'rpc',
            /*
            |--------------------------------------------------------------------------
            | API Version
            |--------------------------------------------------------------------------
            |
            | The semantic version number of this RPC server's API. This is included
            | in the OpenRPC specification and helps clients understand which version
            | of your API they are interacting with. Follow semantic versioning rules.
            |
            */

            'version' => '1.0.0',
            /*
            |--------------------------------------------------------------------------
            | Middleware Stack
            |--------------------------------------------------------------------------
            |
            | Here you may specify the middleware that should be assigned to this
            | RPC server. The middleware will be executed in the order listed here.
            | You may include both global middleware and route-specific middleware.
            |
            | Recommended middleware:
            | - RenderThrowable: Automatically converts exceptions to JSON-RPC errors
            | - ForceJson: Ensures proper JSON content negotiation
            | - BootServer: Initializes the RPC server context
            |
            */

            'middleware' => [
                RenderThrowable::class,
                SubstituteBindings::class,
                'auth:sanctum',
                ForceJson::class,
                BootServer::class,
            ],
            /*
            |--------------------------------------------------------------------------
            | Exposed Methods
            |--------------------------------------------------------------------------
            |
            | Control which RPC methods are exposed through this server. Set this to
            | null to automatically expose all discovered methods, or provide an array
            | of method names to explicitly define which methods should be available.
            | This is useful for creating different API surfaces for different servers.
            |
            | Example: ['users.list', 'users.create', 'posts.*']
            |
            */

            'methods' => null,
            /*
            |--------------------------------------------------------------------------
            | OpenRPC Content Descriptors
            |--------------------------------------------------------------------------
            |
            | Content descriptors define the shape of request parameters and response
            | objects for your RPC methods. These are used to generate the OpenRPC
            | specification document which provides machine-readable API documentation.
            | Register any custom content descriptors your methods require here.
            |
            */

            'content_descriptors' => [
                CursorPaginatorContentDescriptor::create(),
            ],
            /*
            |--------------------------------------------------------------------------
            | JSON Schema Definitions
            |--------------------------------------------------------------------------
            |
            | Define reusable JSON Schema components that describe the structure of
            | complex data types used in your RPC methods. These schemas are referenced
            | in the OpenRPC document and can be used for request/response validation.
            |
            */

            'schemas' => [
                CursorPaginatorSchema::create(),
            ],
        ],
    ],
];

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
// Here endeth thy configuration, noble developer!                            //
// Beyond: code so wretched, even wyrms learned the scribing arts.            //
// Forsooth, they but penned "// TODO: remedy ere long"                       //
// Three realms have fallen since...                                          //
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
//                                                  .~))>>                    //
//                                                 .~)>>                      //
//                                               .~))))>>>                    //
//                                             .~))>>             ___         //
//                                           .~))>>)))>>      .-~))>>         //
//                                         .~)))))>>       .-~))>>)>          //
//                                       .~)))>>))))>>  .-~)>>)>              //
//                   )                 .~))>>))))>>  .-~)))))>>)>             //
//                ( )@@*)             //)>))))))  .-~))))>>)>                 //
//              ).@(@@               //))>>))) .-~))>>)))))>>)>               //
//            (( @.@).              //))))) .-~)>>)))))>>)>                   //
//          ))  )@@*.@@ )          //)>))) //))))))>>))))>>)>                 //
//       ((  ((@@@.@@             |/))))) //)))))>>)))>>)>                    //
//      )) @@*. )@@ )   (\_(\-\b  |))>)) //)))>>)))))))>>)>                   //
//    (( @@@(.@(@ .    _/`-`  ~|b |>))) //)>>)))))))>>)>                      //
//     )* @@@ )@*     (@)  (@) /\b|))) //))))))>>))))>>                       //
//   (( @. )@( @ .   _/  /    /  \b)) //))>>)))))>>>_._                       //
//    )@@ (@@*)@@.  (6///6)- / ^  \b)//))))))>>)))>>   ~~-.                   //
// ( @jgs@@. @@@.*@_ VvvvvV//  ^  \b/)>>))))>>      _.     `bb                //
//  ((@@ @@@*.(@@ . - | o |' \ (  ^   \b)))>>        .'       b`,             //
//   ((@@).*@@ )@ )   \^^^/  ((   ^  ~)_        \  /           b `,           //
//     (@@. (@@ ).     `-'   (((   ^    `\ \ \ \ \|             b  `.         //
//       (*.@*              / ((((        \| | |  \       .       b `.        //
//                         / / (((((  \    \ /  _.-~\     Y,      b  ;        //
//                        / / / (((((( \    \.-~   _.`" _.-~`,    b  ;        //
//                       /   /   `(((((()    )    (((((~      `,  b  ;        //
//                     _/  _/      `"""/   /'                  ; b   ;        //
//                 _.-~_.-~           /  /'                _.'~bb _.'         //
//               ((((~~              / /'              _.'~bb.--~             //
//                                  ((((          __.-~bb.-~                  //
//                                              .'  b .~~                     //
//                                              :bb ,'                        //
//                                              ~~~~                          //
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
