<?php


return [

    'examples' =>  "
        query QueryExamples {
            examples {
                test
            }
        }
    ",

    'examplesCustom' =>  "
        query QueryExamplesCustom {
            examplesCustom {
                test
            }
        }
    ",

    'examplesWithVariables' =>  "
        query QueryExamplesVariables(\$index: Int) {
            examples(index: \$index) {
                test
            }
        }
    ",

    'shorthandExamplesWithVariables' =>  "
        query QueryShorthandExamplesVariables(\$message: String!) {
            echo(message: \$message)
        }
    ",

    'examplesWithAuthorize' =>  "
        query QueryExamplesAuthorize {
            examplesAuthorize {
                test
            }
        }
    ",

    'examplesWithError' =>  "
        query QueryExamplesWithError {
            examplesQueryNotFound {
                test
            }
        }
    ",

    'examplesWithValidation' =>  "
        query QueryExamplesWithValidation(\$index: Int) {
            examples {
                test_validation(index: \$index)
            }
        }
    ",

    'updateExampleCustom' =>  "
        mutation UpdateExampleCustom(\$test: String) {
            updateExampleCustom(test: \$test) {
                test
            }
        }
    ",

    'examplePagination' => "
        query Items(\$take: Int!, \$page: Int!) {
            examplesPagination(take: \$take, page: \$page) {
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
    ",

];
