<?php

namespace Walva\NatagoraBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Walva\NatagoraBundle\Entity\Eleve;
use Walva\NatagoraBundle\Entity\Inscription;
use Walva\NatagoraBundle\Entity\Invite;

/**
 * Evenement
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Walva\NatagoraBundle\Entity\EvenementRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Evenement {

    public static $ETAT_PARTANT_SI_QUOTA = 110;
    public static $ETAT_PARTANT = 120;
    public static $ETAT_COMPLET = 130;
    public static $ETAT_ANNULE = 140;
    public static $ETAT_CONFIRME = 150;
    public static $TYPE_SORTIE = 210;
    public static $TYPE_WEEKEND = 220;
    public static $TYPE_VOYAGE = 230;
    public static $GESTION_INVITE_FOLLOW = 410;
    public static $GESTION_INVITE_PUSH_BOTTOM = 420;
    public static $GESTION_INVITE_TIME_ORDER = 430;
    private $invitesAPlacer;

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate() {
        $this->updatePosition();
    }

    public function getListeDesParticipants() {
        $listeParticipants = array();
        foreach ($this->inscriptions as $inscription) {
            /* @var $inscription Inscription */
            $listeParticipants[] = $inscription;
            if ($inscription->possedeInvite()) {
                $listeParticipants[] = $inscription->getInvite();
            }
        }
        usort($listeParticipants, array($this, "comparerParticipant"));
        return $listeParticipants;
    }

    /**
     * On enregistre les invites pour les placer dans la liste d'inscription
     * par ordre chronologique ou tout en bas (mais tjs en ordre chrono)
     * @param type $invite
     */
    public function enregistrerInviteAPlacer($invite) {
        $this->invitesAPlacer[] = $invite;
        // ensuite il faut trier les inviter par ordre chronologie
        usort($this->invitesAPlacer, array($this, "comparerInvite"));
    }

    public function updatePosition() {
        $this->invitesAPlacer = array();
        $temp = $this->getInscriptions();
        if (!isset($temp))
            return;
        $inscriptions = $this->getInscriptions()->getValues();
        usort($inscriptions, array($this, "comparerInscriptions"));
        $currentPosition = 0;
        foreach ($inscriptions as $inscription) {
            /* @var $inscription Inscription */
            // si on doit placer les invites dans les invite selon la date
            if ($this->typeGestionInvite == self::$GESTION_INVITE_TIME_ORDER) {
                foreach ($this->invitesAPlacer as $invite) {
                    $boolean = ($invite->getDate()->getTimestamp() < $inscription->getDate()->getTimestamp());

                    /* @var $invite Invite */
                    if ($invite->getDate()->getTimestamp() < $inscription->getDate()->getTimestamp()) {
                        $invite->setPosition(++$currentPosition);
                        if ($invite->getPosition() > $this->getMax()) {
                            $invite->setEtat(Inscription::$ETAT_EN_ATTENTE);
                        } else {
                            $invite->setEtat(Inscription::$ETAT_INSCRIT);
                        }
                        $index = array_search($invite, $this->invitesAPlacer);
                        unset($this->invitesAPlacer[$index]);
                    }
                }
            }
            // ici on demande a l'inscription de se mettre à jour
            $currentPosition = $inscription->updatePosition($currentPosition);
        }


        if ($this->typeGestionInvite == self::$GESTION_INVITE_PUSH_BOTTOM) {
            foreach ($this->invitesAPlacer as $invite) {
                $invite->setPosition(++$currentPosition);
                if ($invite->getPosition() > $this->getMax()) {
                    $invite->setEtat(Inscription::$ETAT_EN_ATTENTE);
                } else {
                    $invite->setEtat(Inscription::$ETAT_INSCRIT);
                }
                $index = array_search($invite, $this->invitesAPlacer);
                unset($this->invitesAPlacer[$index]);
            }
        }

        if ($this->typeGestionInvite == self::$GESTION_INVITE_TIME_ORDER) {
            foreach ($this->invitesAPlacer as $invite) {
                $invite->setPosition(++$currentPosition);
                if ($invite->getPosition() > $this->getMax()) {
                    $invite->setEtat(Inscription::$ETAT_EN_ATTENTE);
                } else {
                    $invite->setEtat(Inscription::$ETAT_INSCRIT);
                }
                $index = array_search($invite, $this->invitesAPlacer);
                unset($this->invitesAPlacer[$index]);
            }
        }

        $this->nombreInscrits = $currentPosition;
    }

    public function desinscrireEleve(Eleve $eleve) {
        $inscription = $this->getInscriptionParEleve($eleve);
        if ($inscription == null)
            return false;
        $inscription->annuler();
        $this->updatePosition();
        return true;
    }

    public function getInscriptionParEleve($eleve) {
        $inscriptions = $this->getInscriptions()->getValues();
        foreach ($inscriptions as $inscription) {
            if ($inscription->getEleve()->getId() == $eleve->getId())
                return $inscription;
        }
        return null;
    }

    public function inscrireEleve(Eleve $eleve) {
        $inscription = $this->getInscriptionParEleve($eleve);
        /* @var $inscription Inscription */
        if (!isset($inscription)) {
            $inscription = new Inscription();
            $inscription->setEleve($eleve);
            $inscription->setEvenement($this);
            $inscription->setEtat(Inscription::$ETAT_INSCRIT);
        } elseif (isset($inscription) AND $inscription->estActive())
            throw new \Exception('L\'élève ' . $eleve . ' est deja inscrit.');
        elseif (!$inscription->estActive()) {
            $inscription->setEtat(Inscription::$ETAT_REINSCRIT);
            $this->updatePosition();
        }

        $this->updatePosition();
        return $inscription;
    }

    public function estInscrit(Eleve $e) {
        $idEleve = $e->getId();
        foreach ($this->getInscriptions() as $inscription) {
            /* @var $inscription Inscription */
            $idTemp = $inscription->getEleve()->getId();
            if ($idEleve == $idTemp) {
                return true;
            }
        }
        return false;
    }

    public function comparerInvite(Invite $i1, Invite $i2) {
        $v1 = $i1->getDate()->getTimestamp();
        $v2 = $i2->getDate()->getTimestamp();
        if ($v1 == $v2)
            return 0;
        else
            return ($v1 < $v2) ? -1 : 1;
    }

    public function comparerParticipant($a, $b) {
        $v1 = $a->getPosition();
        $v2 = $b->getPosition();
        if ($v1 == $v2)
            return 0;
        else
            return ($v1 < $v2) ? -1 : 1;
    }

    public function comparerInscriptions(Inscription $i1, Inscription $i2) {
        $v1 = $i1->getDate()->getTimestamp();
        $v2 = $i2->getDate()->getTimestamp();
        if ($v1 == $v2)
            return 0;
        else
            return ($v1 < $v2) ? -1 : 1;
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
     * @ORM\ManyToOne(targetEntity="Walva\NatagoraBundle\Entity\Formateur")
     * @ORM\JoinColumn(nullable=true)
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
     * @ORM\Column(name="typeGestionInvite", type="smallint")
     */
    private $typeGestionInvite = 0;

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
    private $max = 15;

    /**
     * @var integer
     *
     * @ORM\Column(name="nombreinscrits", type="smallint", nullable=true)
     */
    private $nombreInscrits = 0;

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
     * @var string
     *
     * @ORM\Column(name="complet", type="boolean", nullable=true)
     */
    private $complet = true;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Evenement
     */
    public function setDate($date) {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set formateur
     *
     * @param \stdClass $formateur
     * @return Evenement
     */
    public function setFormateur($formateur) {
        $this->formateur = $formateur;

        return $this;
    }

    /**
     * Get formateur
     *
     * @return \stdClass 
     */
    public function getFormateur() {
        return $this->formateur;
    }

    /**
     * Set lieu
     *
     * @param \stdClass $lieu
     * @return Evenement
     */
    public function setLieu($lieu) {
        $this->lieu = $lieu;

        return $this;
    }

    /**
     * Get lieu
     *
     * @return \stdClass 
     */
    public function getLieu() {
        return $this->lieu;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Evenement
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set etat
     *
     * @param integer $etat
     * @return Evenement
     */
    public function setEtat($etat) {
        $this->etat = $etat;

        return $this;
    }

    /**
     * Get etat
     *
     * @return integer 
     */
    public function getEtat() {
        return $this->etat;
    }

    /**
     * Set min
     *
     * @param integer $min
     * @return Evenement
     */
    public function setMin($min) {
        $this->min = $min;

        return $this;
    }

    /**
     * Get min
     *
     * @return integer 
     */
    public function getMin() {
        return $this->min;
    }

    /**
     * Set max
     *
     * @param integer $max
     * @return Evenement
     */
    public function setMax($max) {
        $this->max = $max;
        $this->updatePosition();

        return $this;
    }

    /**
     * Get max
     *
     * @return integer 
     */
    public function getMax() {
        return $this->max;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Evenement
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set formations
     *
     * @param \Walva\NatagoraBundle\Entity\Formation $formations
     * @return Evenement
     */
    public function setFormations(\Walva\NatagoraBundle\Entity\Formation $formations = null) {
        $this->formations = $formations;

        return $this;
    }

    /**
     * Get formations
     *
     * @return \Walva\NatagoraBundle\Entity\Formation 
     */
    public function getFormations() {
        return $this->formations;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->formations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->typeGestionInvite = self::$GESTION_INVITE_PUSH_BOTTOM;
        $this->setDate(new \DateTime('NOW'));
        $this->getDate()->setTime(8, 0, 0);
    }

    /**
     * Add formations
     *
     * @param \Walva\NatagoraBundle\Entity\Formation $formations
     * @return Evenement
     */
    public function addFormation(\Walva\NatagoraBundle\Entity\Formation $formations) {
        $this->formations[] = $formations;

        return $this;
    }

    /**
     * Remove formations
     *
     * @param \Walva\NatagoraBundle\Entity\Formation $formations
     */
    public function removeFormation(\Walva\NatagoraBundle\Entity\Formation $formations) {
        $this->formations->removeElement($formations);
    }

    /**
     * Add inscriptions
     *
     * @param \Walva\NatagoraBundle\Entity\Inscription $inscriptions
     * @return Evenement
     */
    public function addInscription(\Walva\NatagoraBundle\Entity\Inscription $inscriptions) {
        if ($this->estInscrit($inscriptions->getEleve()))
            return;
        $this->inscriptions[] = $inscriptions;

        return $this;
    }

    /**
     * Remove inscriptions
     *
     * @param \Walva\NatagoraBundle\Entity\Inscription $inscriptions
     */
    public function removeInscription(\Walva\NatagoraBundle\Entity\Inscription $inscriptions) {
        $this->inscriptions->removeElement($inscriptions);
    }

    /**
     * Get inscriptions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInscriptions() {
        return $this->inscriptions;
    }

    /**
     * Set nombreInscrits
     *
     * @param integer $nombreInscrits
     * @return Evenement
     */
    public function setNombreInscrits($nombreInscrits) {
        $this->nombreInscrits = $nombreInscrits;

        return $this;
    }

    /**
     * Get nombreInscrits
     *
     * @return integer 
     */
    public function getNombreInscrits() {
        return $this->nombreInscrits;
    }

    /**
     * Set typeGestionInvite
     *
     * @param integer $typeGestionInvite
     * @return Evenement
     */
    public function setTypeGestionInvite($typeGestionInvite) {
        $this->typeGestionInvite = $typeGestionInvite;

        return $this;
    }

    /**
     * Get typeGestionInvite
     *
     * @return integer 
     */
    public function getTypeGestionInvite() {
        return $this->typeGestionInvite;
    }

    /**
     * Set complet
     *
     * @param boolean $complet
     * @return Evenement
     */
    public function setComplet($complet) {
        $this->complet = $complet;

        return $this;
    }

    /**
     * Get complet
     *
     * @return boolean 
     */
    public function getComplet() {
        return $this->complet;
    }

}