<?php

declare(strict_types=1);

return [

    'examples' => '
        query QueryExamples {
            examples {
                test
            }
        }
    ',

    'examplesCustom' => '
        query QueryExamplesCustom {
            examplesCustom {
                test
            }
        }
    ',

    'examplesWithConfigAlias' => '
        query examplesConfigAlias($index: Int) {
            examplesConfigAlias(index: $index) {
                test
            }
        }
    ',

    'examplesWithVariables' => '
        query QueryExamplesVariables($index: Int) {
            examples(index: $index) {
                test
            }
        }
    ',

    'examplesWithFilterVariables' => '
        query QueryExamplesWithFilterVariables($filter: ExampleFilterInput) {
            examplesFiltered(filter: $filter) {
                test
            }
        }
    ',

    'shorthandExamplesWithVariables' => '
        query QueryShorthandExamplesVariables($message: String!) {
            echo(message: $message)
        }
    ',

    'examplesWithAuthorize' => '
        query QueryExamplesAuthorize {
            examplesAuthorize {
                test
            }
        }
    ',

    'examplesWithAuthorizeMessage' => '
        query QueryExamplesAuthorizeMessage {
            examplesAuthorizeMessage {
                test
            }
        }
    ',

    'examplesWithError' => '
        query QueryExamplesWithError {
            examplesQueryNotFound {
                test
            }
        }
    ',

    'examplesWithValidation' => '
        query QueryExamplesWithValidation($index: Int) {
            examples {
                test_validation(index: $index)
            }
        }
    ',

    'updateExampleCustom' => '
        mutation UpdateExampleCustom($test: String) {
            updateExampleCustom(test: $test) {
                test
            }
        }
    ',

    'exampleMiddleware' => '
        query examplesMiddleware($index: Int) {
            examplesMiddleware(index: $index) {
                test
            }
        }
    ',

    'examplePagination' => '
        query Items($take: Int!, $page: Int!) {
            examplesPagination(take: $take, page: $page) {
                items {
                    test
                }
                cursor {
                    total
                    perPage
                    currentPage
                    hasPages
                }
            }
        }
    ',

];
