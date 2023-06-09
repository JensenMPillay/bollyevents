<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class, orphanRemoval: true,  cascade: ['persist', 'remove'])]
    private Collection $items;

    #[ORM\Column(length: 255)]
    private ?string $status = self::STATUS_CART;

    /**
     * In Progress Status : Cart Status.
     *
     * @var string
     */
    const STATUS_CART = 'CART_STEP';

    /**
     * In Progress Status : Checkout Status.
     *
     * @var string
     */
    const STATUS_CHECKOUT = 'CHECKOUT_STEP';

    /**
     * In Progress Status : Payed Status.
     *
     * @var string
     */
    const STATUS_PAYED = 'PAYED_STEP';

    /**
     * In Progress Status : Delivery Waiting Status.
     *
     * @var string
     */
    const STATUS_DELIVERY_WAITING = 'DELIVERY_WAITING_STEP';

    /**
     * In Progress Status : Ended Status.
     *
     * @var string
     */
    const STATUS_DELIVERED = 'DELIVERED_STEP';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $delivery_date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $delivery_address = null;

    #[ORM\Column(nullable: true)]
    private ?float $shipping_fees = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?float $distance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $payment_id = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        foreach ($this->getItems() as $existingItem) {
            // Update Quantity
            if ($existingItem->equals($item)) {
                $existingItem->setQuantity(
                    $existingItem->getQuantity() + $item->getQuantity()
                );
                return $this;
            }
        }

        $this->items[] = $item;
        $item->setOrderRef($this);

        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getOrderRef() === $this) {
                $item->setOrderRef(null);
            }
        }

        return $this;
    }

    /**
     * Removes all items from the order.
     *
     * @return $this
     */
    public function removeItems(): self
    {
        foreach ($this->getItems() as $item) {
            $this->removeItem($item);
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Calculates the order total.
     *
     * @return float
     */
    public function getTotal(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            $total += $item->getTotal();
        }

        return $total;
    }

    public function getDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->delivery_date;
    }

    public function setDeliveryDate(?\DateTimeImmutable $delivery_date): self
    {
        $this->delivery_date = $delivery_date;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->delivery_address;
    }

    public function setDeliveryAddress(?string $delivery_address): self
    {
        $this->delivery_address = $delivery_address;

        return $this;
    }

    public function getShippingFees(): ?float
    {
        return $this->shipping_fees;
    }

    public function setShippingFees(?float $shipping_fees): self
    {
        $this->shipping_fees = $shipping_fees;

        return $this;
    }

    /**
     * Calculates the order total with ShippingFees.
     *
     * @return float
     */
    public function getTotalWithShippingFees(): float
    {
        $totalWithShippingFees = 0;

        $totalProducts = $this->getTotal();

        $shipping_fees = $this->getShippingFees() != null ? $this->getShippingFees() : 0;

        $totalWithShippingFees = $totalProducts + $shipping_fees;

        return $totalWithShippingFees;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function getDistanceKm(): ?float
    {
        return round($this->distance / 1000, 2);
    }

    public function setDistance(?float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getPaymentId(): ?string
    {
        return $this->payment_id;
    }

    public function setPaymentId(?string $payment_id): self
    {
        $this->payment_id = $payment_id;

        return $this;
    }
}
