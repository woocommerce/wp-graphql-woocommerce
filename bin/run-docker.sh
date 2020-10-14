#!/usr/bin/env bash

set -eu

print_usage_instructions() {
	echo "Usage: $0 build|run [-e=env-file ] [-a|-t]";
	exit 1
}

if [ -z "$1" ]; then
	print_usage_instructions
fi

env_file=".env.dist";

subcommand=$1; shift
case "$subcommand" in
    "build" )
        while getopts ":at" opt; do
            case ${opt} in
                a )
                docker build -f docker/app.Dockerfile \
                    -t woographql-app:latest \
                    --build-arg WP_VERSION=${WP_VERSION-5.4} \
                    --build-arg PHP_VERSION=${PHP_VERSION-7.4} \
                    .
                    ;;
                t )
                docker build -f docker/app.Dockerfile \
                    -t woographql-app:latest \
                    --build-arg WP_VERSION=${WP_VERSION-5.4} \
                    --build-arg PHP_VERSION=${PHP_VERSION-7.4} \
                    .

                docker build -f docker/testing.Dockerfile \
                    -t woographql-testing:latest \
                    --build-arg USE_XDEBUG=${USE_XDEBUG-} \
                    .
                    ;;
                \? ) print_usage_instructions;;
                * ) print_usage_instructions;;
            esac
        done
        shift $((OPTIND -1))
        ;;
    "run" )
        while getopts "e:at" opt; do
            case ${opt} in
				e )
				env_file=${OPTARG};
				if [ ! -f $env_file ]; then
					echo "No file found at $env_file"
				fi
				;;
                a ) docker-compose up --scale testing=0;;
                t )
				source ${env_file}
                docker-compose run --rm \
                    -e STRIPE_API_PUBLISHABLE_KEY=${STRIPE_API_PUBLISHABLE_KEY-} \
                    -e STRIPE_API_SECRET_KEY=${STRIPE_API_SECRET_KEY-} \
                    -e SUITES=${SUITES-} \
                    -e COVERAGE=${COVERAGE-} \
                    -e DEBUG=${DEBUG-} \
                    -e SKIP_TESTS_CLEANUP=${SKIP_TESTS_CLEANUP-} \
                    testing --scale app=0
                    ;;
                \? ) print_usage_instructions;;
                * ) print_usage_instructions;;
            esac
        done
        shift $((OPTIND -1))
        ;;

    \? ) print_usage_instructions;;
    * ) print_usage_instructions;;
esac