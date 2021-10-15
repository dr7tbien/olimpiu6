<?php
require_once 'defines/CDH_awsDefinesAll.php';


#define( 'CDH_DB_LOCAL_TO_CLOUD_BUCKET_ROOT_NAME', 'cdh-dblocal-to-cloud-' . CDH_DOMINIO_SIN_PUNTO );
#define( 'CDH_FILES_LOCAL_TO_CLOUD_BUCKET_ROOT_NAME', 'cdhdblocaltocloudstatuscript-' . CDH_DOMINIO_SIN_PUNTO );
define( 'CDH_FILES_LOCAL_TO_CLOUD_BUCKET_ROOT_NAME', 'cdhfileslocaltocloudstatuscript-' . CDH_DOMINIO_SIN_PUNTO );


define( 'CDH_FILES_LOCAL_TO_CLOUD_BUCKET_TAG_KEY',   'Line');
define( 'CDH_FILES_LOCAL_TO_CLOUD_BUCKET_TAG_VALUE', 'BBB');

#define( 'CDH_FILES_LOCAL_TO_CLOUD_FILE_USER_DATA_TEMPLATE',  'store/scriptDbLocalToCloudTemplate.sh' );
#define( 'CDH_FILES_LOCAL_TO_CLOUD_FILE_USER_DATA',           'store/scriptDbLocalToCloud.sh' );
define( 'CDH_FILES_LOCAL_TO_CLOUD_FILE_USER_DATA_TEMPLATE',  'store/scriptFilesLocalToCloudTemplate.sh' );
define( 'CDH_FILES_LOCAL_TO_CLOUD_FILE_USER_DATA',           'store/scriptFilesLocalToCloud.sh' );



#define('CDH_DATABASE_LOCALDIR_BACKUP',      "store/CDH_dbLocal/");
#define('CDH_DATABASE_LOCALDIR_BACKUP_URL',  "http://" . CDH_DOMINIO_CON_PUNTO . "/modules/cloudator/app/" . CDH_DATABASE_LOCALDIR_BACKUP );

define('CDH_FILES_LOCALDIR_BACKUP',      "cdh_fileslocal/");
define('CDH_FILES_LOCALDIR_BACKUP_URL',  "http://" . CDH_DOMINIO_CON_PUNTO . "/modules/cloudator/app/" . CDH_FILES_LOCALDIR_BACKUP );
#nombre zip
define('CDH_FILES_LOCALFILE_BACKUP', "backup.zip");


#define( 'CDH_FILES_LOCAL_TO_CLOUD_policy_document', 'store/CDH_POLICY_db_local_to_cloud.json' ); 
define( 'CDH_FILES_LOCAL_TO_CLOUD_policy_document', 'store/CDH_POLICY_files_local_to_cloud.json' ); 

define('CDH_FILES_LOCAL_TO_CLOUD_createPolicy', [
    'Description'       => 'CDH ' . CDH_DOMINIO_SIN_PUNTO . ' Policy for CDH_FILES_LOCAL_TO_CLOUD',
    'PolicyDocument'    => file_get_contents( CDH_FILES_LOCAL_TO_CLOUD_policy_document ) , // REQUIRED     policy.json
    'PolicyName'        => 'CDH_FILES_LOCAL_TO_CLOUD_' . CDH_DOMINIO_SIN_PUNTO , // REQUIRED
    'Tags' => [
        [
            'Key'   => 'Name', // REQUIRED
            'Value' => 'CDH_FILES_LOCAL_TO_CLOUD_' . CDH_DOMINIO_SIN_PUNTO , // REQUIRED
        ],
        // ...
    ],
]);

define( 'CDH_FILES_LOCAL_TO_CLOUD_role_document', 'store/CDH_FILES_LOCAL_TO_CLOUD_role_document.json' ); 

define('CDH_FILES_LOCAL_TO_CLOUD_createRole', [
    'AssumeRolePolicyDocument'  => file_get_contents( CDH_FILES_LOCAL_TO_CLOUD_role_document ),

    'RoleName'                  => 'CDH-FILES-LOCAL-TO-CLOUD-' . CDH_DOMINIO_SIN_PUNTO,
    'Tags' => [
        [
            'Key'               => 'Name', // REQUIRED
            'Value'             => 'CDH-FILES-LOCAL-TO-CLOUD-' . CDH_DOMINIO_SIN_PUNTO, // REQUIRED
        ],

    ],

]);

define('CDH_FILES_LOCAL_TO_CLOUD_createInstanceProfile', [
    'InstanceProfileName' => CDH_FILES_LOCAL_TO_CLOUD_createRole['RoleName'],
]);



define('CDH_FILES_LOCAL_TO_CLOUD_createSecurityGroup', [
    'Description' => 'Security Group for CDH_FILES_LOCAL_TO_CLOUD ' .  CDH_DOMINIO_SIN_PUNTO , # REQUIRED
    #'DryRun' => true || false,
    'GroupName' => 'CDH_FILES_LOCAL_TO_CLOUD_'   . CDH_DOMINIO_SIN_PUNTO, # REQUIRED
    'TagSpecifications' => [
                            [
                                'ResourceType' => 'security-group',
                                  'Tags' => [
                                              [
                                                  'Key'     => 'Name',
                                                  'Value'   => 'CDH_FILES_LOCAL_TO_CLOUD_'   . CDH_DOMINIO_SIN_PUNTO,
                                              ],
                          ],
                  ],
    ],
    'VpcId' => '',
]);

define('CDH_FILES_LOCAL_TO_CLOUD_authorizeSecurityGroupIngress', [
    'GroupId' => "", # $groupId,
    'IpPermissions' => [

        [
            'FromPort'              => 22,
            'IpProtocol'            => 'tcp',
            'IpRanges' => [
                [
                    'CidrIp'      => '0.0.0.0/0',
                    'Description' => 'cdh ' . CDH_DOMINIO_CON_PUNTO . ' FILES2C instance access 22', 
                ],
            ],
            'ToPort'          => 22
        ],
        [
            'FromPort'              => 80,
            'IpProtocol'            => 'tcp',
            'IpRanges' => [
                [
                    'CidrIp'      => '0.0.0.0/0',
                    'Description' => 'cdh ' . CDH_DOMINIO_CON_PUNTO . ' FILES2C instance access 80',
                ],
            ],
            'ToPort'          => 80
        ],
        [
            'FromPort'              => 443,
            'IpProtocol'            => 'tcp',
            'IpRanges' => [
                [
                    'CidrIp'      => '0.0.0.0/0',
                    'Description' => 'cdh ' . CDH_DOMINIO_CON_PUNTO . ' FILES2C instance access 443',
                ],
            ],
            'ToPort'          => 443
        ],


    ],
]);


define( 'CDH_FILES_LOCAL_TO_CLOUD_sshAccessFilePath', 'store/KEYPAIR_CDH_FILES_LOCAL_TO_CLOUD_' . CDH_DOMINIO_SIN_PUNTO . '.txt');


define('CDH_FILES_LOCAL_TO_CLOUD_keyPairCreate', [
    'KeyName' => 'CDH_FILES_LOCAL_TO_CLOUD_'   . CDH_DOMINIO_SIN_PUNTO, // REQUIRED
    'TagSpecifications' => [
        [
            'ResourceType' => 'key-pair',
            'Tags' => [
                [
                    'Key' => 'Name',
                    'Value' => 'CDH_FILES_LOCAL_TO_CLOUD_'   . CDH_DOMINIO_SIN_PUNTO,
                ],
                // ...
            ],
        ],
        // ...
    ],
]);


define('CDH_FILES_LOCAL_TO_CLOUD_instanceRun', [
    #'AdditionalInfo' => '<string>',
    'BlockDeviceMappings' => [
        [
            'DeviceName' => '/dev/sda1',
            'Ebs' => [
                'VolumeSize' => 30,
            ],
        ],
        // ...
    ],

    'IamInstanceProfile' => [
        'Arn' => '',
    ],
    'ImageId' => '',

    'InstanceType' => 't3.medium',

    'KeyName' => CDH_FILES_LOCAL_TO_CLOUD_keyPairCreate['KeyName'],

    'MaxCount' => 1, // REQUIRED

    'MinCount' => 1, // REQUIRED
    'Monitoring' => [
        'Enabled' => true, // REQUIRED
    ],
    'NetworkInterfaces' => [
        [
            #'AssociateCarrierIpAddress' => true || false,
            'AssociatePublicIpAddress' => true,
            'DeleteOnTermination' => true,
            #'Description' => '<string>',
            'DeviceIndex' => 0,
            'Groups' => [],

            'PrivateIpAddresses' => [
                [
                    'Primary' => true,
                    #'PrivateIpAddress' => '<string>',
                ],
                //...
            ],
            'SubnetId' => '<string>',
        ],
        // ...
    ],

    'TagSpecifications' => [
        [
            'ResourceType' => 'instance',
            'Tags' => [
                [
                    'Key' => 'Name',
                    'Value' => 'CDH_FILES_LOCAL_TO_CLOUD_' . CDH_DOMINIO_SIN_PUNTO

                ],

            ],
        ],
        // ...
    ],

    'UserData' => @base64_encode( file_get_contents( CDH_FILES_LOCAL_TO_CLOUD_FILE_USER_DATA)),
    # 'UserData' => base64_encode( file_get_contents( CDH_DB_LOCAL_TO_CLOUD_FILE_USER_DATA)) ,
]);






























?>