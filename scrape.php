<?php

/*
 This file is part of Phacman
 http://github.com/AndrewRose/Phacman
 http://andrewrose.co.uk
 License: GPL; see below
 Copyright Andrew Rose (hello@andrewrose.co.uk) 2014

    Phacman is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Phacman is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Phacman.  If not, see <http://www.gnu.org/licenses/>
*/


namespace Phacman;

class Scrape
{
	const dbPath = '/var/lib/pacman/';
	private $sections = ['name', 'version', 'desc', 'url', 'arch', 'builddate', 'installdate', 'packager', 'size', 'reason', 'validation', 'filename', 'csize', 'isize', 'md5sum', 'sha256sum', 'pgpsig'];
	public $db;

	public $repoInsertStmt;
	public $groupInsertStmt;
	public $licenseInsertStmt;
	public $packageInsertStmt;
	public $packageRepoInsertStmt;
	public $packageLocalInsertStmt;

	public $importedPackages = [];
	public $repos = [];
	public $licenses = [];
	public $groups = [];
	public $packageGroups = [];
	public $packageLicenses = [];
	public $packageDepends = [];
	public $packageConflicts = [];
	public $packageProvides = [];
	public $packageOptdepends = [];
	public $packageReplaces = [];
	public $packageFiles = [];
	public $packageBackups = [];

	public function __construct()
	{
		$this->db = new \PDO('mysql:host=localhost;dbname=phacman', 'root', '');

		$this->repoInsertStmt = $this->db->prepare('INSERT INTO phacman_repo(name) VALUES(:name) ON DUPLICATE KEY UPDATE id=last_insert_id(id);');
		$this->groupInsertStmt = $this->db->prepare('INSERT INTO phacman_group(name) VALUES(:name) ON DUPLICATE KEY UPDATE id=last_insert_id(id);');
		$this->licenseInsertStmt = $this->db->prepare('INSERT INTO phacman_license(name) VALUES(:name) ON DUPLICATE KEY UPDATE id=last_insert_id(id);');

		$stmt = $this->db->query('SELECT id, name FROM phacman_repo');
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
		{
			$this->repos[$row['name']] = $row['id'];
		}

		$stmt = $this->db->query('SELECT id, name FROM phacman_group');
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
		{
			$this->groups[$row['name']] = $row['id'];
		}

		$stmt = $this->db->query('SELECT id, name FROM phacman_license');
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
		{
			$this->licenses[$row['name']] = $row['id'];
		}

		$this->packageInsertStmt = $this->db->prepare('INSERT INTO phacman_package(name, version) VALUES(:name, :version) ON DUPLICATE KEY UPDATE id=last_insert_id(id);');

		$this->packageRepoInsertStmt = $this->db->prepare('INSERT INTO phacman_package_repo(repoId, packageId, description, url, arch, builddate, packager, validation, filename, csize, isize, md5sum, sha256sum, pgpsig)
VALUES(:repoId, :packageId, :description, :url, :arch, :builddate, :packager, :validation, :filename, :csize, :isize, :md5sum, :sha256sum, :pgpsig) ON DUPLICATE KEY UPDATE
repoId = VALUES(repoId), packageId = VALUES(packageId), description = VALUES(description), url = VALUES(url), arch = VALUES(arch), builddate = VALUES(builddate),
packager = VALUES(packager), validation = VALUES(validation), filename = VALUES(filename), csize = VALUES(csize), isize = VALUES(isize), md5sum = VALUES(md5sum), sha256sum = VALUES(sha256sum),
pgpsig = VALUES(pgpsig);');

		$this->packageLocalInsertStmt = $this->db->prepare('INSERT INTO phacman_package_local(repoId, packageId, description, url, arch, builddate, installdate, packager, size, reason, validation)
VALUES(:repoId, :packageId, :description, :url, :arch, from_unixtime(:builddate), from_unixtime(:installdate), :packager, :size, :reason, :validation) ON DUPLICATE KEY UPDATE
repoId = VALUES(repoId), packageId = VALUES(packageId), description = VALUES(description), url = VALUES(url), arch = VALUES(arch), builddate = VALUES(builddate),
installdate = VALUES(installdate), packager = VALUES(packager), size = VALUES(size), reason = VALUES(reason), validation = VALUES(validation)');
	}

	public function importLocal()
	{
		$repoId = $this->checkRepo('local');

		if(FALSE === ($repoHandler = opendir(self::dbPath.'/local')))
		{
			echo 'Failed to open repo: '.$repo."\n";
			exit();
		}

		while(FALSE !== ($pkgdir = readdir($repoHandler)))
		{
			if($pkgdir == '.' || $pkgdir == '..') continue;
			$desc = file_get_contents(self::dbPath.'/local/'.$pkgdir.'/desc');
			$desc .= file_get_contents(self::dbPath.'/local/'.$pkgdir.'/files');

			$desc = explode("\n\n%", $desc);

			$packageLocalDets = [
				':repoId' => $repoId,
				':name' => FALSE,
				':version' => FALSE,
				':description' => FALSE,
				':url' => FALSE,
				':arch' => FALSE,
				':builddate' => FALSE,
				':installdate' => FALSE,
				':packager' => FALSE,
				':size' => FALSE,
				':reason' => FALSE,
				':validation' => FALSE
			];

			$dets = $this->parse($desc, $packageLocalDets);

			if(isset($packageLocalDets[':name']) && isset($packageLocalDets[':version']))
			{
				if($this->packageInsertStmt->execute([':name' => $packageLocalDets[':name'], ':version' => $packageLocalDets[':version']]))
				{
					$name = $packageLocalDets[':name'];
					$version = $packageLocalDets[':version'];
					unset($packageLocalDets[':name']);
					unset($packageLocalDets[':version']);
					$packageLocalDets[':packageId'] = $this->db->lastInsertId();

					if($this->packageLocalInsertStmt->execute($packageLocalDets) && $this->packageLocalInsertStmt->rowCount() == 1)
					{
// TODO: import $dets
					}
				}
			}
		}
	}

	public function importSync($repoName)
	{
		$repoId = $this->checkRepo($repoName);

		$repoHandler = new \PharData(self::dbPath.'sync/'.$repoName.'.db');

		foreach($repoHandler as $file)
		{
			$file = $file->getPathName();

			$desc = file_get_contents($file.'/desc');
			$desc .= file_get_contents($file.'/depends');

			$desc = explode("\n\n%", $desc);

			$packageRepoDets = [
				':repoId' => $repoId,
				':description' => FALSE,
				':url' => FALSE,
				':arch' => FALSE,
				':builddate' => FALSE,
				':packager' => FALSE,
				':validation' => FALSE,
				':filename' => FALSE, 
				':csize' => FALSE,
				':isize' => FALSE,
				':md5sum' => FALSE,
				':sha256sum' => FALSE,
				':pgpsig' => FALSE
			];

			$this->parse($desc, $packageRepoDets);
			if(isset($packageRepoDets[':name']) && isset($packageRepoDets[':version']))
			{
				if($this->packageInsertStmt->execute([':name' => $packageRepoDets[':name'], ':version' => $packageRepoDets[':version']]))
				{
					unset($packageRepoDets[':name']);
					unset($packageRepoDets[':version']);
					$packageRepoDets[':packageId'] = $this->db->lastInsertId();
					if($this->packageRepoInsertStmt->execute($packageRepoDets) && $this->packageRepoInsertStmt->rowCount() == 1)
					{
// TODO: import $dets
					}
				}
			}
		}
	}

	public function checkRepo($name)
	{
		$name = trim($name);
		if(!isset($this->repos[$name]))
		{
			$this->repoInsertStmt->execute([':name' => $name]);
			$row = $this->repoInsertStmt->fetch(\PDO::FETCH_ASSOC);
			$this->repos[$name] = $this->db->lastInsertId();
			return $row['id'];
		}
		return $this->repos[$name];
	}

	public function insertGroup($name)
	{
		$name = trim($name);
		$this->groupInsertStmt->execute([':name' => $name]);
		$row = $this->groupInsertStmt->fetch(\PDO::FETCH_ASSOC);
		$this->groups[$name] = $this->db->lastInsertId();
		return $row['id'];
	}

	public function insertLicense($name)
	{
		$name = trim($name);
		$this->licenseInsertStmt->execute([':name' => $name]);
		$row = $this->licenseInsertStmt->fetch(\PDO::FETCH_ASSOC);
		$this->licenses[$name] = $this->db->lastInsertId();
		return $row['id'];
	}

	public function parse($desc, &$packageDets)
	{
		$ret = [
			'groups' => [],
			'licenses' => [],
			'depends' => [],
			'conflicts' => [],
			'provides' => [],
			'optdepends' => [],
			'replaces' => [],
			'files' => [],
			'backup' => []
		];

		foreach($desc as $section)
		{
			$tmp = explode("\n", $section);
			$sectionName = strtolower(str_replace('%','',$tmp[0]));

			foreach($tmp as $idx => $data)
			{
				if(empty(trim($data)))
				{
					unset($tmp[$idx]);
				}
			}

			if(in_array($sectionName, $this->sections))
			{
				if($sectionName == 'desc') $sectionName = 'description';
				$packageDets[':'.$sectionName] = $tmp[1]; //array_slice($tmp, 1);
			}
			else
			{
				// we rely on %NAME% having already been set in $packageDets as it is first in the desc file
				$packageName = $packageDets[':name'];

				$values = array_slice($tmp, 1);
				foreach($values as $value)
				{
					$value = trim($value);
					switch($sectionName)
					{
						case 'groups':
						{
							if(!isset($this->groups[$value]))
							{
								$this->insertGroup($value);
							}

							$ret['groups'][$value] = $this->groups[$value];
						}
						break;

						case 'license':
						{
							$value = explode(':', $value);
							if(isset($value[1]))
							{
								$value=trim($value[1]);
								$value = str_replace('"', '', $value); // only seen with custom:"icu"
							}
							else
							{
								$value = trim(str_replace('"', '', $value[0]));
							}

							if(!isset($this->licenses[$value]))
							{
								$this->insertLicense($value);
							}

							$ret['licenses'][$value] = $this->licenses[$value];
						}
						break;

						case 'depends':
						{
							$version = FALSE;
							$relop = $this->getRelop($value);
							if($relop)
							{
								$value = explode($relop, $value);
								$version = $value[1];
								$value = $value[0];
							}

							$ret['depends'][$value] = [$relop, $version];
						}
						break;

						case 'conflicts':
						{
							$version = FALSE;
							$relop = $this->getRelop($value);
							if($relop)
							{
								$value = explode($relop, $value);
								$version = $value[1];
								$value = $value[0];
							}

							$ret['conflicts'][$value] =  [$relop, $version];
						}
						break;

						case 'provides':
						{
							$version = FALSE;
							if(FALSE !== strpos($value, '='))
							{
								$value = explode('=', $value);
								$version = $value[1];
								$value = $value[0];
							}

							$ret['provides'][$value] = $version;
						}
						break;

						case 'optdepends':
						{
							$dets = '';
							$value = explode(':',$value);
							if(isset($value[1]))
							{
								$dets = trim($value[1]);
								$value = $value[0];
							}
							else
							{
								$value = $value[0];
							}

							$ret['optdepends'][$value] = $dets;
						}
						break;

						case 'replaces':
						{
							array_push($ret['replaces'], $value);
						}
						break;

						case 'files':
						{
							array_push($ret['files'], $value);
						}
						break;

						case 'backup':
						{
							$value = explode("\t", $value);
							$ret['backup'][$value[0]] = $value[1];
						}
						break;
					}
				}
			}
		}
		return $ret;
	}

	function getRelop($value)
	{
		foreach(['<=', '>=', '=', '<', '>'] as $relop)
		{
			if(FALSE !== strpos($value, $relop))
			{
				return $relop;
			}
		}
		return FALSE;
	}
}


$s = new Scrape();
$s->importSync('core');
$s->importSync('extra');
$s->importSync('community');
$s->importSync('testing');
$s->importLocal();
