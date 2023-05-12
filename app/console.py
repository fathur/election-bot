import math
import os
import sentry_sdk

import click
import tweepy

from app.services.poll_maker import PollMaker
from app.services.reporting import Reporting


@click.group()
def run():
    pass


@run.command()
def create_poll():
    click.echo("Executing create poll")
    PollMaker.run()
    click.echo("Executed create poll")


@run.command()
@click.option(
    "-i",
    "--interval",
    required=True,
    show_default=True,
    type=click.Choice(
        ["daily", "weekly", "monthly", "quarterly", "yearly"], case_sensitive=True
    ),
)
def generate_report(interval):
    click.echo(f"Executing generate report {interval}")
    Reporting.run(interval)
    click.echo(f"Executed generate report {interval}")


@run.command()
def test():
    arr = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n"]
    click.echo(arr[0])
    click.echo(arr[-1])
    # ra = 14
    # len(arr)
    # loop_times = math.ceil(len(arr) / ra)
    # start = 0
    # total = ra
    # for _ in range(0, loop_times):
    #     click.echo(arr[start:total])
    #     start += ra
    #     total += ra
