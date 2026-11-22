@Library(['testing', 'dockerHelpers', 'pipelines']) _

phpPackagePipeline(
    dependencies: 'update',
    matrix: [
        [name: 'PHP 8.6', image: 'php:8.6', update: ''],
    ],
    satis: true,
)
