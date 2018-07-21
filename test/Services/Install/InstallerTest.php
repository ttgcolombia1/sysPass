<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Services\Install;

use DI\Container;
use PHPUnit\Framework\TestCase;
use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\Services\Crypt\MasterPassService;
use SP\Services\Install\InstallData;
use SP\Services\Install\Installer;
use SP\Storage\Database\DBUtil;
use SP\Storage\Database\MySQLHandler;
use function SP\Test\setupContext;

require_once 'DbTestUtilTrait.php';

/**
 * Class InstallerTest
 *
 * @package SP\Tests\Services\Install
 */
class InstallerTest extends TestCase
{
    use DbTestUtilTrait;

    const DB_NAME = 'syspass_test';

    /**
     * @var Container
     */
    private static $dic;

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        self::$dic = setupContext();
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testRun()
    {
        $params = new InstallData();
        $params->setDbAdminUser('root');
        $params->setDbAdminPass('syspass');
        $params->setDbName(self::DB_NAME);
        $params->setDbHost('syspass-db');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);
        $installer->run($params);

        $configData = self::$dic->get(Config::class)->getConfigData();

        $this->assertEquals($params->getDbName(), $configData->getDbName());
        $this->assertEquals($params->getDbHost(), $configData->getDbHost());
        $this->assertEquals(3306, $configData->getDbPort());
        $this->assertTrue(preg_match('/sp_\w+/', $configData->getDbUser()) === 1);
        $this->assertNotEmpty($configData->getDbPass());
        $this->assertEquals($params->getSiteLang(), $configData->getSiteLang());

        $this->assertTrue(self::$dic->get(MasterPassService::class)->checkMasterPassword($params->getMasterPassword()));

        $this->dropDatabase(self::DB_NAME);
        $this->dropUser($configData->getDbUser(), SELF_IP_ADDRESS);
        $this->dropUser($configData->getDbUser(), SELF_HOSTNAME);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testFailDbHostName()
    {
        $params = new InstallData();
        $params->setDbAdminUser('root');
        $params->setDbAdminPass('syspass');
        $params->setDbName(self::DB_NAME);
        $params->setDbHost('fail');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(2002);

        $installer->run($params);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testFailDbHostIp()
    {
        $params = new InstallData();
        $params->setDbAdminUser('root');
        $params->setDbAdminPass('syspass');
        $params->setDbName(self::DB_NAME);
        $params->setDbHost('192.168.0.1');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(2002);

        $installer->run($params);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testFailDbHostPort()
    {
        $params = new InstallData();
        $params->setDbAdminUser('root');
        $params->setDbAdminPass('syspass');
        $params->setDbName(self::DB_NAME);
        $params->setDbHost('syspass-db:3307');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(2002);

        $installer->run($params);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testFailDbUser()
    {
        $params = new InstallData();
        $params->setDbAdminUser('toor');
        $params->setDbAdminPass('syspass');
        $params->setDbName(self::DB_NAME);
        $params->setDbHost('syspass-db');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(1045);

        $installer->run($params);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testFailDbPass()
    {
        $params = new InstallData();
        $params->setDbAdminUser('root');
        $params->setDbAdminPass('test');
        $params->setDbName(self::DB_NAME);
        $params->setDbHost('syspass-db');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');

        $installer = self::$dic->get(Installer::class);

        $this->expectException(SPException::class);
        $this->expectExceptionCode(1045);

        $installer->run($params);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testHostingMode()
    {
        $this->createDatabase(self::DB_NAME);
        $this->createUser('syspass_user', '123456', self::DB_NAME);

        $params = new InstallData();
        $params->setDbAdminUser('syspass_user');
        $params->setDbAdminPass('123456');
        $params->setDbName(self::DB_NAME);
        $params->setDbHost('syspass-db');
        $params->setAdminLogin('admin');
        $params->setAdminPass('syspass_admin');
        $params->setMasterPassword('00123456789');
        $params->setSiteLang('en_US');
        $params->setHostingMode(true);

        $installer = self::$dic->get(Installer::class);
        $installer->run($params);

        $this->assertTrue(DBUtil::checkDatabaseExist(self::$dic->get(MySQLHandler::class), self::DB_NAME));

        $configData = self::$dic->get(Config::class)->getConfigData();

        $this->assertEquals($params->getDbName(), $configData->getDbName());
        $this->assertEquals($params->getDbHost(), $configData->getDbHost());
        $this->assertEquals(3306, $configData->getDbPort());
        $this->assertNotEmpty($configData->getDbPass());
        $this->assertEquals($params->getSiteLang(), $configData->getSiteLang());

        $this->assertTrue(self::$dic->get(MasterPassService::class)->checkMasterPassword($params->getMasterPassword()));

        $this->dropDatabase(self::DB_NAME);
        $this->dropUser('syspass_user', SELF_IP_ADDRESS);
    }

    protected function tearDown()
    {
        @unlink(CONFIG_FILE);
    }
}