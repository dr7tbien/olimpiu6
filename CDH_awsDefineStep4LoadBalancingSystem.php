<?php
require_once 'defines/CDH_awsDefinesAll.php';

require_once 'CDH_awsDefineStep00RecordSetSystem.php';


define('CDH_LOAD_BALANCING_SECURITY_GROUP', [
    'Description' => 'Security Group for ' . CDH_DOMINIO_SIN_PUNTO . " Load Balancing", # REQUIRED
    #'DryRun' => true || false,
    'GroupName' => 'CDH_LOAD_BALANCING_'   . CDH_DOMINIO_SIN_PUNTO, # REQUIRED
    'TagSpecifications' => [
              [
                  'ResourceType' => 'security-group',
                  'Tags' => [
                              [
                                  'Key'     => 'Name',
                                  'Value'   => 'CDH_LOAD_BALANCING_'   . CDH_DOMINIO_SIN_PUNTO,
                              ],
                  ],
             ],
    ],
    'VpcId' => '',
]);


define('CDH_LOAD_BALANCING_TARGET_GROUP_080', [
    'HealthCheckEnabled'            => true,
    'HealthCheckIntervalSeconds'    => 10,
    'HealthCheckPath'               => '/',
    'HealthCheckPort'               => 'traffic-port',
    'HealthCheckProtocol'           => 'HTTP',
    'HealthCheckTimeoutSeconds'     => 5,
    'HealthyThresholdCount'         => 3,
    'Matcher' => [
        'HttpCode'                  => '200,302', // REQUIRED
    ],
    'Name'                          => 'CDH-80-'   . CDH_DOMINIO_SIN_PUNTO, // REQUIRED
    'Port'                          => 80,
    'Protocol'                      => 'HTTP',
    'Tags' => [
        [
            'Key' => 'Name', // REQUIRED
            'Value' => 'CDH-80-'   . CDH_DOMINIO_SIN_PUNTO,
        ],
    ],
    'TargetType'                    => 'instance', //'instance|ip|lambda',
    'UnhealthyThresholdCount'       => 5,
    'VpcId'                         => ''
]);

define('CDH_LOAD_BALANCING_TARGET_GROUP_443', [
    'HealthCheckEnabled'            => true,
    'HealthCheckIntervalSeconds'    => 10,
    'HealthCheckPath'               => '/',
    'HealthCheckPort'               => 'traffic-port',
    'HealthCheckProtocol'           => 'HTTPS',
    'HealthCheckTimeoutSeconds'     => 5,
    'HealthyThresholdCount'         => 3,
    'Matcher' => [
        'HttpCode'                  => '200,302', // REQUIRED
    ],
    'Name'                          => 'CDH-443-'   . CDH_DOMINIO_SIN_PUNTO, // REQUIRED
    'Port'                          => 443,
    'Protocol'                      => 'HTTPS',
    'Tags' => [
        [
            'Key'   => 'Name', 
            'Value' => 'CDH-443-'   . CDH_DOMINIO_SIN_PUNTO,
        ],
    ],
    'TargetType'                    => 'instance', //'instance|ip|lambda',
    'UnhealthyThresholdCount'       => 5,
    'VpcId'                         => ''

]);

define('CDH_LOAD_BALANCING_LOAD_BALANCER', [
    #'LoadBalancerName'  => 'CDH_'   . CDH_DOMINIO_SIN_PUNTO,
    'Name'              => 'CDH-'   . CDH_DOMINIO_SIN_PUNTO, // REQUIRED
    'SecurityGroups'    => [],
    'Subnets'           => [],
    'Tags' => [
        [
            'Key'   => 'Name', // REQUIRED
            'Value' => 'CDH-'   . CDH_DOMINIO_SIN_PUNTO,
        ],
        // ...
    ],
    'Type' => 'application',

]);

define('CDH_LOAD_BALANCING_LISTENER_080', [
    'DefaultActions' => [ // REQUIRED
        [

            'ForwardConfig' => [

                'TargetGroups' => [
                    [
                        #'TargetGroupArn' => $arnTargetGroup,
                        'TargetGroupArn' => '',
                        'Weight' => 1,
                    ],
                    // ...
                ],
            ],

            #'TargetGroupArn' => $arnTargetGroup,
            'TargetGroupArn' => '',
            'Type' => 'forward', // REQUIRED
        ],
        // ...
    ],
    #'LoadBalancerArn' => $arnLoadBalancer, // REQUIRED
    'LoadBalancerArn' => '', // REQUIRED
    'Port' => 80, // REQUIRED
    'Protocol' => 'HTTP', // REQUIRED
    'Tags' => [
        [
            'Key' => 'Name', // REQUIRED
            'Value' => "080_" . CDH_DOMINIO_SIN_PUNTO,
        ],
        // ...
    ],
]);




define('CDH_LOAD_BALANCING_LISTENER_443', [
    'Certificates' => [
        [
            #'CertificateArn' => $certificateArn,
            'CertificateArn' => '',
            //'IsDefault'      => true,
        ],
        // ...
    ],

    'DefaultActions' => [ // REQUIRED
        [


            'ForwardConfig' => [

                'TargetGroups' => [
                    [
                        #'TargetGroupArn' => $arnTargetGroup,
                        'TargetGroupArn' => '',
                        'Weight' => 1,
                    ],
                ],
            ],
            #'TargetGroupArn' => $arnTargetGroup,
            'TargetGroupArn' => '',
            'Type' => 'forward',
        ],

    ],
    #'LoadBalancerArn'   => $arnLoadBalancer, // REQUIRED
    'LoadBalancerArn'   => '', // REQUIRED
    'Port'              => 443, // REQUIRED
    'Protocol'          => 'HTTPS', // REQUIRED
    'SslPolicy'         => 'ELBSecurityPolicy-2016-08',
    'Tags' => [
        [
            'Key' => 'Name', // REQUIRED
            'Value' => "443_" . CDH_DOMINIO_SIN_PUNTO,
        ],
        // ...
    ],

]);


define( 'CDH_LOAD_BALANCING_REQUEST_CERTIFICATE', [
    'DomainName'                => CDH_DOMINIO_CON_PUNTO, // REQUIRED
    'DomainValidationOptions'   => [
        [
            'DomainName'        => CDH_DOMINIO_CON_PUNTO, // REQUIRED
            'ValidationDomain'  => CDH_DOMINIO_CON_PUNTO, // REQUIRED
        ],
    ],

    'SubjectAlternativeNames' => [ '*.' . CDH_DOMINIO_CON_PUNTO ] ,

    'Tags' => [
        [
            'Key'   => 'Name', // REQUIRED
            'Value' => CDH_DOMINIO_CON_PUNTO,
        ],
    ],
    'ValidationMethod' => 'DNS'

]);

define ('CDH_HOSTED_ZONE', CDH_RECORDSET_SYSTEM_HOSTED_ZONE);
/*
var_dump(CDH_LOAD_BALANCING_REQUEST_CERTIFICATE);
die("\n194");
*/
?>