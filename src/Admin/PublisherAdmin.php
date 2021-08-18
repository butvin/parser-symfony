<?php


namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;


class PublisherAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form)
    {
        $form->add('user');
        $form->add('url');
    }

    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter->add('id');
        $filter->add('user');
        $filter->add('url');
        $filter->add('name');
        $filter->add('createdAt');
        $filter->add('updatedAt');
        $filter->add('deletedAt');
    }

    protected function configureListFields(ListMapper $list)
    {
        $list->addIdentifier('id');
        $list->add('user');
        $list->add('url');
        $list->add('name');
        $list->add('createdAt');
        $list->add('updatedAt');
        $list->add('deletedAt');
    }
}