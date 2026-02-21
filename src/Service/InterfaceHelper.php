<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * InterfaceHelper.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InterfaceHelper implements InterfaceHelperInterface
{
    private array $cache = [];
    private EntityManagerInterface $entityManager;

    /**
     * InterfaceHelper constructor.
     */
    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * generate.
     */
    public function generate(mixed $entity, ?string $field = null): mixed
    {
        $referEntity = is_object($entity) ? $entity : ($entity ? new $entity() : false);
        $classname = $referEntity ? get_class($referEntity) : uniqid();

        if ($classname && !empty($this->cache[$classname]) && !$field) {
            return $this->cache[$classname];
        }

        $this->cache[$classname] = $interface = $referEntity && method_exists($referEntity, 'getInterface') ? $referEntity::getInterface() : [];
        $this->cache[$classname]['metadata'] = !empty($this->cache[$classname]['metadata']) ? $this->cache[$classname]['metadata']
            : ($classname && $referEntity ? $this->entityManager->getClassMetadata($classname)->getFieldNames() : []);
        $this->cache[$classname]['referClass'] = $classname;
        $this->cache[$classname]['name'] = !empty($interface['name']) ? $interface['name'] : null;
        $this->cache[$classname]['orderBy'] = !empty($interface['orderBy']) ? $interface['orderBy']
            : ($referEntity && method_exists($referEntity, 'getAdminName') ? 'adminName' : 'id');
        $this->cache[$classname]['orderSort'] = !empty($interface['orderSort']) ? $interface['orderSort'] : 'ASC';
        $this->cache[$classname]['masterField'] = !empty($interface['masterField']) ? $interface['masterField'] : false;
        $this->cache[$classname]['masterFieldGetter'] = !empty($interface['masterField']) ? 'get'.ucfirst($interface['masterField']) : false;
        $this->cache[$classname]['masterFieldSetter'] = !empty($interface['masterField']) ? 'set'.ucfirst($interface['masterField']) : false;
        $this->cache[$classname]['disabledActions'] = !empty($interface['disabledActions']) ? $interface['disabledActions'] : [];
        $this->cache[$classname]['buttons'] = !empty($interface['buttons']) ? $interface['buttons'] : [];
        $this->cache[$classname]['columns'] = !empty($interface['columns']) ? $interface['columns'] : [];
        $this->cache[$classname]['export'] = !empty($interface['export']) ? $interface['export'] : [];
        $this->cache[$classname]['showAsConfig'] = !empty($interface['show']);
        $this->cache[$classname]['show'] = !empty($interface['show']) ? $interface['show'] : $this->cache[$classname]['metadata'];
        $this->cache[$classname]['asCode'] = !empty($interface['asCode']) ? $interface['asCode'] : [];
        $this->cache[$classname]['email'] = !empty($interface['email']) ? $interface['email'] : [];

        return $field && !empty($this->cache[$classname][$field]) ? $this->cache[$classname][$field] : $this->cache[$classname];
    }
}
