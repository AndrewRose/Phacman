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
	public $stmts = [
		'repoInsert' => 'INSERT INTO phacman_repo(name) VALUES(:name) ON DUPLICATE KEY UPDATE id=last_insert_id(id);',
		'groupInsert' => 'INSERT INTO phacman_group(name) VALUES(:name) ON DUPLICATE KEY UPDATE id=last_insert_id(id);',
		'licenseInsert' => 'INSERT INTO phacman_license(name) VALUES(:name) ON DUPLICATE KEY UPDATE id=last_insert_id(id);',
		'packageInsert' => 'INSERT INTO phacman_package(name) VALUES(:name) ON DUPLICATE KEY UPDATE id=last_insert_id(id);',
		'packageVersionInsert' => 'INSERT INTO phacman_package_version(packageId, version) VALUES(:packageId, :version) ON DUPLICATE KEY UPDATE id=last_insert_id(id);',
		'packageRepoInsert' => 'INSERT INTO phacman_package_repo(repoId, packageVersionId, description, url, arch, builddate, packager, validation, filename, csize, isize, md5sum, sha256sum, pgpsig)
VALUES(:repoId, :packageVersionId, :description, :url, :arch, :builddate, :packager, :validation, :filename, :csize, :isize, :md5sum, :sha256sum, :pgpsig) ON DUPLICATE KEY UPDATE
repoId = VALUES(repoId), packageVersionId = VALUES(packageVersionId), description = VALUES(description), url = VALUES(url), arch = VALUES(arch), builddate = VALUES(builddate),
packager = VALUES(packager), validation = VALUES(validation), filename = VALUES(filename), csize = VALUES(csize), isize = VALUES(isize), md5sum = VALUES(md5sum), sha256sum = VALUES(sha256sum),
pgpsig = VALUES(pgpsig);',
		'packageLocalInsert' => 'INSERT INTO phacman_package_local(repoId, packageVersionId, description, url, arch, builddate, installdate, packager, size, reason, validation)
VALUES(:repoId, :packageVersionId, :description, :url, :arch, from_unixtime(:builddate), from_unixtime(:installdate), :packager, :size, :reason, :validation) ON DUPLICATE KEY UPDATE
repoId = VALUES(repoId), packageVersionId = VALUES(packageVersionId), description = VALUES(description), url = VALUES(url), arch = VALUES(arch), builddate = VALUES(builddate),
installdate = VALUES(installdate), packager = VALUES(packager), size = VALUES(size), reason = VALUES(reason), validation = VALUES(validation)',
		'packageFileInsert' => 'INSERT IGNORE INTO phacman_package_files(packageVersionId, file) values(:packageVersionId, :file);',
		'packageDependInsert' => 'INSERT IGNORE INTO phacman_package_depends(packageVersionId, packageDependId, relop, version) VALUES(:packageVersionId, :packageDependId, :relop, :version);',
		'packageConflictInsert' => 'INSERT IGNORE INTO phacman_package_conflicts(packageVersionId, packageConflictId, relop, version) VALUES(:packageVersionId, :packageConflictId, :relop, :version);',
		'packageOptdependInsert' => 'INSERT IGNORE INTO phacman_package_optdepends(packageVersionId, optdependPackageId, details) VALUES(:packageVersionId, :optdependPackageId, :details);',
		'packageGroupInsert' => 'INSERT IGNORE INTO phacman_package_groups(packageVersionId, groupId) VALUES(:packageVersionId, :groupId);',
		'packageLicenseInsert' => 'INSERT IGNORE INTO phacman_package_license(packageVersionId, licenseId) VALUES(:packageVersionId, :licenseId);',
		'packageProvideInsert' => 'INSERT IGNORE INTO phacman_package_provides(packageVersionId, providePackageId, providePackageVersionId) VALUES(:packageVersionId, :providePackageId, :providePackageVersionId);',
		'packageReplaceInsert' => 'INSERT IGNORE INTO phacman_package_replaces(packageVersionId, replacePackageId) VALUES(:packageVersionId, :replacePackageId);',
		'packageBackupInsert' => 'INSERT IGNORE INTO phacman_package_backup(packageVersionId, file, md5sum) VALUES(:packageVersionId, :file, :md5sum);'
	];

	public function __construct()
	{
		$this->db = new \PDO('mysql:host=localhost;dbname=phacman', 'root', '');
		$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$this->stmts['repoInsert'] = $this->db->prepare($this->stmts['repoInsert']);
		$this->stmts['groupInsert'] = $this->db->prepare($this->stmts['groupInsert']);
		$this->stmts['licenseInsert'] = $this->db->prepare($this->stmts['licenseInsert']);

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

		$this->stmts['packageInsert'] = $this->db->prepare($this->stmts['packageInsert']);
		$this->stmts['packageVersionInsert'] = $this->db->prepare($this->stmts['packageVersionInsert']);
		$this->stmts['packageRepoInsert'] = $this->db->prepare($this->stmts['packageRepoInsert']);
		$this->stmts['packageLocalInsert'] = $this->db->prepare($this->stmts['packageLocalInsert']);
		$this->stmts['packageFileInsert'] = $this->db->prepare($this->stmts['packageFileInsert']);
		$this->stmts['packageDependInsert'] = $this->db->prepare($this->stmts['packageDependInsert']);
		$this->stmts['packageConflictInsert'] = $this->db->prepare($this->stmts['packageConflictInsert']);
		$this->stmts['packageOptdependInsert'] = $this->db->prepare($this->stmts['packageOptdependInsert']);
		$this->stmts['packageGroupInsert'] = $this->db->prepare($this->stmts['packageGroupInsert']);
		$this->stmts['packageLicenseInsert'] = $this->db->prepare($this->stmts['packageLicenseInsert']);
		$this->stmts['packageProvideInsert'] = $this->db->prepare($this->stmts['packageProvideInsert']);
		$this->stmts['packageReplaceInsert'] = $this->db->prepare($this->stmts['packageReplaceInsert']);
		$this->stmts['packageBackupInsert'] = $this->db->prepare($this->stmts['packageBackupInsert']);
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
			if($pkgdir == 'ALPM_DB_VERSION') continue;

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
				':reason' => 0,
				':validation' => FALSE
			];

			$sections = $this->parse($desc, $packageLocalDets);

			if(isset($packageLocalDets[':name']) && isset($packageLocalDets[':version']))
			{
				if($this->stmts['packageInsert']->execute([':name' => $packageLocalDets[':name']]))
				{
					$packageId = $this->db->lastInsertId();

					if($this->stmts['packageVersionInsert']->execute([':packageId' => $packageId, ':version' => $packageLocalDets[':version']]))
					{
						$packageVersionId = $this->db->lastInsertId();

						$name = $packageLocalDets[':name'];
						$version = $packageLocalDets[':version'];
						unset($packageLocalDets[':name']);
						unset($packageLocalDets[':version']);
						$packageLocalDets[':packageVersionId'] = $this->db->lastInsertId();
if(!$packageLocalDets[':size'])
{
	$packageLocalDets[':size'] = 0;
}
//print_r($packageLocalDets);
						if($this->stmts['packageLocalInsert']->execute($packageLocalDets) && $this->stmts['packageLocalInsert']->rowCount() == 1)
						{
							$this->importDets($packageId, $packageVersionId, $sections);
						}
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
			// /depends has been merged with /desc
			//$desc .= file_get_contents($file.'/depends');

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
				':isize' => 0,
				':md5sum' => FALSE,
				':sha256sum' => FALSE,
				':pgpsig' => FALSE
			];

			$sections = $this->parse($desc, $packageRepoDets);
			if(isset($packageRepoDets[':name']) && isset($packageRepoDets[':version']))
			{
				if($this->stmts['packageInsert']->execute([':name' => $packageRepoDets[':name']]))
				{
					$packageId = $this->db->lastInsertId();

					if($this->stmts['packageVersionInsert']->execute([':packageId' => $packageId, ':version' => $packageRepoDets[':version']]))
					{
						$packageVersionId = $this->db->lastInsertId();
						unset($packageRepoDets[':name']);
						unset($packageRepoDets[':version']);
						$packageRepoDets[':packageVersionId'] = $this->db->lastInsertId();
						if(!$this->stmts['packageRepoInsert']->execute($packageRepoDets) && $this->stmts['packageRepoInsert']->rowCount() == 1)
						{
							$this->importDets($packageId, $packageVersionId, $sections);
						}
					}
				}
			}
		}
	}

	public function importDets($packageId, $packageVersionId, $sections)
	{
		foreach($sections as $section => $dets)
		{
			switch($section)
			{
				case 'files':
				{
					// we use a plain query for speed.
					$firstFile = array_pop($dets);
					if(!$firstFile) break;
					$query1 = 'INSERT INTO phacman_package_files(packageVersionId, file) VALUES';
					$query2 = '('.$packageVersionId.", '".$firstFile."')";
					$count = 0;
					foreach($dets as $file)
					{
						if($count>20)
						{
							$this->db->query($query1.' '.$query2);
							$query2 = '('.$packageVersionId.", '".$file."')";
							$count = 0;
						}
						else
						{
							$count++;
							$query2 .= ',('.$packageVersionId.", '".$file."')";
						}
					}
					if($query2) $this->db->query($query1.' '.$query2);
				}
				break;

				case 'depends':
				{
					foreach($dets as $package => $dets)
					{
						$this->stmts['packageInsert']->execute([':name' => $package]);
						$packageDependId = $this->db->lastInsertId();
						$this->stmts['packageDependInsert']->execute([':packageVersionId' => $packageVersionId, ':packageDependId' => $packageDependId, ':relop' => $dets[0], ':version' => $dets[1]]);
					}
				}
				break;

				case 'conflicts':
				{
					foreach($dets as $package => $dets)
					{
						$this->stmts['packageInsert']->execute([':name' => $package]);
						$packageConflictId = $this->db->lastInsertId();
						$this->stmts['packageConflictInsert']->execute([':packageVersionId' => $packageVersionId, ':packageConflictId' => $packageConflictId, ':relop' => $dets[0], ':version' => $dets[1]]);
					}
				}
				break;

				case 'optdepends':
				{
					foreach($dets as $packageName => $details)
					{
						$this->stmts['packageInsert']->execute([':name' => $packageName]);
						$optdependPackageId = $this->db->lastInsertId();
						$this->stmts['packageOptdependInsert']->execute([':packageVersionId' => $packageVersionId, ':optdependPackageId' => $optdependPackageId, ':details' => $details]);
					}
				}
				break;

				case 'groups':
				{
					foreach($dets as $groupName => $groupId)
					{
						$this->stmts['packageGroupInsert']->execute([':packageVersionId' => $packageVersionId, ':groupId' => $groupId]);
					}
				}
				break;

				case 'licenses':
				{
					foreach($dets as $groupName => $licenseId)
					{
						$this->stmts['packageLicenseInsert']->execute([':packageVersionId' => $packageVersionId, ':licenseId' => $licenseId]);
					}
				}
				break;

				case 'provides':
				{
					foreach($dets as $providePackageName => $providePackageVersion)
					{
						$this->stmts['packageInsert']->execute([':name' => $providePackageName]);
						$providePackageId = $this->db->lastInsertId();

						if($providePackageVersion)
						{
							$this->stmts['packageVersionInsert']->execute([':packageId' => $providePackageId, ':version' => $providePackageVersion]);
							$providePackageVersionId = $this->db->lastInsertId();
						}
						else
						{
							$providePackageVersionId = NULL;
						}

						$this->stmts['packageProvideInsert']->execute([':packageVersionId' => $packageVersionId, ':providePackageId' => $providePackageId, ':providePackageVersionId' => $providePackageVersionId]);
					}
				}
				break;

				case 'backup':
				{
					foreach($dets as $fileName => $md5sum)
					{
						$this->stmts['packageBackupInsert']->execute([':packageVersionId' => $packageVersionId, ':file' => $fileName, ':md5sum' => $md5sum]);
					}
				}
				break;

				case 'replaces':
				{
					foreach($dets as $idx => $replacePackageName)
					{
						$this->stmts['packageInsert']->execute([':name' => $replacePackageName]);
						$replacePackageId = $this->db->lastInsertId();
						$this->stmts['packageReplaceInsert']->execute([':packageVersionId' => $packageVersionId, ':replacePackageId' => $replacePackageId]);
					}
				}
				break;
			}
		}
	}

	public function checkRepo($name)
	{
		$name = trim($name);
		if(!isset($this->repos[$name]))
		{
			$this->repoInsert->execute([':name' => $name]);
			$this->repos[$name] = $this->db->lastInsertId();
			return $this->repos[$name];
		}
		return $this->repos[$name];
	}

	public function insertGroup($name)
	{
		$name = trim($name);
		$this->stmts['groupInsert']->execute([':name' => $name]);
		$this->groups[$name] = $this->db->lastInsertId();
		return $this->groups[$name];;
	}

	public function insertLicense($name)
	{
		$name = trim($name);
		$this->stmts['licenseInsert']->execute([':name' => $name]);
		$this->licenses[$name] = $this->db->lastInsertId();
		return $this->licenses[$name];
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
				if(isset($tmp[1])) $packageDets[':'.$sectionName] = $tmp[1]; //array_slice($tmp, 1);
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

try {
	$s->importSync('core');
	$s->importSync('extra');
	$s->importSync('community');
	$s->importSync('testing');
	$s->importLocal();
} catch (PDOException $e) {
	echo $e->getMessage();
	exit();
}
