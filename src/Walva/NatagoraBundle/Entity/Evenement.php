<?php

namespace Walva\NatagoraBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Evenement
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Walva\NatagoraBundle\Entity\EvenementRepository")
 */
class Evenement
{
    
    public static $ETAT_PARTANT_SI_QUOTA = 110;
    public static $ETAT_PARTANT = 120;
    public static $ETAT_COMPLET = 130;
    public static $ETAT_ANNULE = 140;
    public static $ETAT_CONFIRME = 150;
    
    public static $TYPE_SORTIE = 210;
    public static $TYPE_WEEKEND = 220;
    public static $TYPE_VOYAGE = 230;
    
    public function getNombreInscription(){
        return count($this->getInscriptions());
    }
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var \stdClass
     *
     * @ORM\Column(name="formateur", type="object", nullable=true)
     */
    private $formateur;

    /**
     * @var \stdClass
     *
     * @ORM\ManyToOne(targetEntity="Walva\NatagoraBundle\Entity\Lieu")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lieu;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="etat", type="smallint")
     */
    private $etat = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="min", type="smallint", nullable=true)
     */
    private $min = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="max", type="smallint", nullable=true)
     */
    private $max = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;
    
    /**
     * @var string
     *
     * @ORM\ManyToMany(targetEntity="Walva\NatagoraBundle\Entity\Formation")
     * @ORM\JoinColumn(nullable=true)
     */
    private $formations;
    
    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="Walva\NatagoraBundle\Entity\Inscription", mappedBy="evenement")
     */
    private $inscriptions;
    
    


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Evenement
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set formateur
     *
     * @param \stdClass $formateur
     * @return Evenement
     */
    public function setFormateur($formateur)
    {
        $this->formateur = $formateur;
    
        return $this;
    }

    /**
     * Get formateur
     *
     * @return \stdClass 
     */
    public function getFormateur()
    {
        return $this->formateur;
    }

    /**
     * Set lieu
     *
     * @param \stdClass $lieu
     * @return Evenement
     */
    public function setLieu($lieu)
    {
        $this->lieu = $lieu;
    
        return $this;
    }

    /**
     * Get lieu
     *
     * @return \stdClass 
     */
    public function getLieu()
    {
        return $this->lieu;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Evenement
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set etat
     *
     * @param integer $etat
     * @return Evenement
     */
    public function setEtat($etat)
    {
        $this->etat = $etat;
    
        return $this;
    }

    /**
     * Get etat
     *
     * @return integer 
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * Set min
     *
     * @param integer $min
     * @return Evenement
     */
    public function setMin($min)
    {
        $this->min = $min;
    
        return $this;
    }

    /**
     * Get min
     *
     * @return integer 
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set max
     *
     * @param integer $max
     * @return Evenement
     */
    public function setMax($max)
    {
        $this->max = $max;
    
        return $this;
    }

    /**
     * Get max
     *
     * @return integer 
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Evenement
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set formations
     *
     * @param \Walva\NatagoraBundle\Entity\Formation $formations
     * @return Evenement
     */
    public function setFormations(\Walva\NatagoraBundle\Entity\Formation $formations = null)
    {
        $this->formations = $formations;
    
        return $this;
    }

    /**
     * Get formations
     *
     * @return \Walva\NatagoraBundle\Entity\Formation 
     */
    public function getFormations()
    {
        return $this->formations;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->formations = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add formations
     *
     * @param \Walva\NatagoraBundle\Entity\Formation $formations
     * @return Evenement
     */
    public function addFormation(\Walva\NatagoraBundle\Entity\Formation $formations)
    {
        $this->formations[] = $formations;
    
        return $this;
    }

    /**
     * Remove formations
     *
     * @param \Walva\NatagoraBundle\Entity\Formation $formations
     */
    public function removeFormation(\Walva\NatagoraBundle\Entity\Formation $formations)
    {
        $this->formations->removeElement($formations);
    }

    /**
     * Add inscriptions
     *
     * @param \Walva\NatagoraBundle\Entity\Inscription $inscriptions
     * @return Evenement
     */
    public function addInscription(\Walva\NatagoraBundle\Entity\Inscription $inscriptions)
    {
        $this->inscriptions[] = $inscriptions;
    
        return $this;
    }

    /**
     * Remove inscriptions
     *
     * @param \Walva\NatagoraBundle\Entity\Inscription $inscriptions
     */
    public function removeInscription(\Walva\NatagoraBundle\Entity\Inscription $inscriptions)
    {
        $this->inscriptions->removeElement($inscriptions);
    }

    /**
     * Get inscriptions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInscriptions()
    {
        return $this->inscriptions;
    }
}