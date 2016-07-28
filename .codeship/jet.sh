#!/bin/bash

echo Starting test run..
CONTAINER_BASE_DIR=/var/app/current

i=0
MYSQL_HEALTH_CMD="mysqladmin ping -hfezdb -ufez -pfez"
HEALTH_MSG=$(${MYSQL_HEALTH_CMD} 2>&1)
while ! [[ -n "${HEALTH_MSG}" && ${HEALTH_MSG} != *"failed"* && ${HEALTH_MSG} != *"denied"* ]]; do
  i=`expr ${i} + 1`
  if [[ ${i} -ge ${MAX_LOOPS} ]]; then
    echo "$(date) - MySQL still not reachable, giving up"
    exit 1
  fi
  echo "$(date) - waiting for MySQL..."
  sleep 1
  HEALTH_MSG=$(${MYSQL_HEALTH_CMD} 2>&1)
done

echo Creating schema..
cd ${CONTAINER_BASE_DIR}/tests/application
php init.php schema

echo Starting upgrading..
UPGRADE_RES=$(curl -s http://fez/upgrade/index.php?upgradeOnly=1 | grep succeeded)
if [[ "${UPGRADE_RES}" == "" ]]; then
  echo "failed to run upgrade scripts! :("
  exit 1
fi

echo Seeding SQL data..
php init.php seed

echo Running tests..

cd ${CONTAINER_BASE_DIR}
./tests/application/run-tests.sh
